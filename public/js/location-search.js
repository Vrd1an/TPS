/**
 * location-search.js
 * Modul GIS Lanjutan: Multi-Provider Geocoding (Nominatim, Photon, Local Index),
 * Batas Wilayah Polygon, Smart Zoom, Highlighting Spasial, Riwayat Pencarian & Cache
 * Arsitektur Clean Code & Modular untuk SIG Penentuan Kelayakan Lokasi TPS
 */

(function (window, document) {
    'use strict';

    // Helper Fuzzy Distance (Levenshtein Distance) untuk Toleransi Typo
    function levenshteinDistance(a, b) {
        if (a.length === 0) return b.length;
        if (b.length === 0) return a.length;
        const matrix = [];
        for (let i = 0; i <= b.length; i++) matrix[i] = [i];
        for (let j = 0; j <= a.length; j++) matrix[0][j] = j;

        for (let i = 1; i <= b.length; i++) {
            for (let j = 1; j <= a.length; j++) {
                if (b.charAt(i - 1) === a.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }
        return matrix[b.length][a.length];
    }

    class LocationSearchManager {
        constructor(config = {}) {
            this.map = config.map || null;
            this.marker = config.marker || null;
            this.onLocationSelected = config.onLocationSelected || null;
            this.debounceTimer = null;
            this.isGeocoding = false;

            this.currentBoundaryLayer = null;
            this.currentHighlightLayer = null;
            this.searchHistoryKey = 'sig_tps_search_history';
            this.cachePrefix = 'sig_tps_geocode_cache_';

            this.state = {
                displayName: '',
                adminLevel: '',
                lat: null,
                lng: null,
                address: '',
                village: '',
                subdistrict: '',
                district: '',
                province: '',
                areaKm2: null,
                geometryType: 'Point'
            };

            this.initDOMElements();
            this.bindEvents();
            this.loadSearchHistory();
        }

        initDOMElements() {
            this.elements = {
                searchInput: document.getElementById('nominatim-search-input'),
                searchResults: document.getElementById('nominatim-search-results'),
                searchLoading: document.getElementById('nominatim-search-loading'),
                searchHistoryList: document.getElementById('search-history-list'),
                btnUseLocation: document.getElementById('btn-use-location'),
                infoPanel: document.getElementById('location-info-panel'),
                panelName: document.getElementById('info-panel-name'),
                panelAdminLevel: document.getElementById('info-panel-admin-level'),
                panelArea: document.getElementById('info-panel-area'),
                panelCoords: document.getElementById('info-panel-coords'),
                panelAddress: document.getElementById('info-panel-address'),
                panelVillage: document.getElementById('info-panel-village'),
                panelSubdistrict: document.getElementById('info-panel-subdistrict'),
                panelDistrict: document.getElementById('info-panel-district'),
                panelProvince: document.getElementById('info-panel-province'),
                toastContainer: document.getElementById('location-toast-container')
            };
        }

        bindEvents() {
            if (this.elements.searchInput) {
                this.elements.searchInput.addEventListener('focus', () => {
                    this.showSearchHistoryDropdown();
                });

                this.elements.searchInput.addEventListener('input', (e) => {
                    clearTimeout(this.debounceTimer);
                    const query = e.target.value.trim();
                    if (query.length < 2) {
                        this.showSearchHistoryDropdown();
                        return;
                    }
                    this.debounceTimer = setTimeout(() => {
                        this.searchLocationMultiProvider(query);
                    }, 400);
                });
            }

            if (this.elements.btnUseLocation) {
                this.elements.btnUseLocation.addEventListener('click', () => {
                    this.applyLocationToForm();
                });
            }

            document.addEventListener('click', (e) => {
                if (this.elements.searchResults && !this.elements.searchResults.contains(e.target) && e.target !== this.elements.searchInput) {
                    this.hideSearchResults();
                }
            });
        }

        setMapAndMarker(map, marker) {
            this.map = map;
            this.marker = marker;
        }

        /**
         * 1. Multi-Provider Fallback Geocoding (Nominatim -> Photon -> Local Fuzzy Index)
         */
        async searchLocationMultiProvider(query) {
            // Cek jika input adalah koordinat Lat, Lng
            const coordRegex = /^\s*(-?\d+(\.\d+)?)\s*,\s*(-?\d+(\.\d+)?)\s*$/;
            const match = query.match(coordRegex);
            if (match) {
                const lat = parseFloat(match[1]);
                const lng = parseFloat(match[3]);
                this.hideSearchResults();
                this.handleLocationSelect({
                    lat: lat,
                    lon: lng,
                    display_name: `Koordinat Input (${lat.toFixed(6)}, ${lng.toFixed(6)})`,
                    type: 'point'
                });
                return;
            }

            // Cek Cache Lokal terlebih dahulu untuk optimasi performa
            const cached = this.getCache(query);
            if (cached) {
                this.renderSearchResults(cached);
                return;
            }

            this.showLoading(true);

            try {
                // Provider 1: Nominatim OpenStreetMap (dengan polygon_geojson=1)
                let results = await this.queryNominatim(query);

                // Provider 2: Fallback ke Photon Komoot API jika Nominatim kosong
                if (!results || results.length === 0) {
                    results = await this.queryPhoton(query);
                }

                // Provider 3: Fallback ke Local Index Fuzzy Search
                if (!results || results.length === 0) {
                    results = this.queryLocalFuzzyIndex(query);
                }

                this.showLoading(false);

                if (results && results.length > 0) {
                    this.setCache(query, results);
                    this.renderSearchResults(results);
                } else {
                    this.showToast('Wilayah / lokasi tidak ditemukan. Coba masukkan nama jalan/desa/kecamatan lain.', 'warning');
                    this.hideSearchResults();
                }
            } catch (err) {
                this.showLoading(false);
                // Fallback terakhir ke Local Index jika jaringan bermasalah
                const localResults = this.queryLocalFuzzyIndex(query);
                if (localResults && localResults.length > 0) {
                    this.renderSearchResults(localResults);
                } else {
                    this.showToast('⚠️ Gagal terhubung ke server Geocoding. Membuka mode offline.', 'error');
                }
            }
        }

        async queryNominatim(query) {
            try {
                const encodedQuery = encodeURIComponent(query + ', Cianjur, Jawa Barat');
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodedQuery}&polygon_geojson=1&addressdetails=1&limit=5&countrycodes=id`;
                const res = await fetch(url, { headers: { 'Accept-Language': 'id-ID,id;q=0.9' } });
                if (!res.ok) return [];
                const data = await res.json();
                if (data.length > 0) return data;

                // Query tanpa Cianjur jika pencarian spesifik kosong
                const fallbackUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&polygon_geojson=1&addressdetails=1&limit=5&countrycodes=id`;
                const fRes = await fetch(fallbackUrl, { headers: { 'Accept-Language': 'id-ID,id;q=0.9' } });
                return fRes.ok ? await fRes.json() : [];
            } catch (e) {
                return [];
            }
        }

        async queryPhoton(query) {
            try {
                const url = `https://photon.komoot.io/api/?q=${encodeURIComponent(query + ' Cianjur')}&limit=5`;
                const res = await fetch(url);
                if (!res.ok) return [];
                const data = await res.json();
                if (!data.features) return [];

                return data.features.map(f => {
                    const props = f.properties || {};
                    const coords = f.geometry ? f.geometry.coordinates : [107.14, -6.81];
                    return {
                        lat: coords[1],
                        lon: coords[0],
                        display_name: `${props.name || query}, ${props.city || props.district || 'Cianjur'}, ${props.state || 'Jawa Barat'}`,
                        geojson: f.geometry,
                        address: {
                            village: props.name,
                            subdistrict: props.district || props.city,
                            county: 'Kabupaten Cianjur',
                            state: 'Jawa Barat'
                        },
                        type: props.osm_value || 'administrative'
                    };
                });
            } catch (e) {
                return [];
            }
        }

        queryLocalFuzzyIndex(query) {
            if (!window.CIANJUR_ADMIN_INDEX) return [];
            const q = query.toLowerCase().trim();

            const matches = window.CIANJUR_ADMIN_INDEX.map(item => {
                const nameLower = item.name.toLowerCase();
                const dist = levenshteinDistance(q, nameLower);
                let score = 0;
                if (nameLower.includes(q)) score += 50;
                if (dist <= 3) score += (30 - dist * 5);
                return { item, score };
            }).filter(m => m.score > 10).sort((a, b) => b.score - a.score);

            return matches.slice(0, 5).map(m => ({
                lat: m.item.lat,
                lon: m.item.lng,
                display_name: `${m.item.name}, ${m.item.kecamatan}, Kabupaten Cianjur`,
                type: m.item.type.toLowerCase(),
                address: {
                    village: m.item.type === 'Desa' ? m.item.name : '',
                    subdistrict: m.item.kecamatan,
                    county: 'Kabupaten Cianjur',
                    state: 'Jawa Barat'
                }
            }));
        }

        renderSearchResults(results) {
            if (!this.elements.searchResults) return;

            this.elements.searchResults.innerHTML = '';

            results.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3.5 py-2.5 hover:bg-emerald-50 border-b border-gray-100 last:border-0 flex items-start gap-2 transition-colors';

                const title = item.display_name.split(',')[0];
                const typeBadge = (item.type || 'wilayah').toUpperCase();

                btn.innerHTML = `
                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2 a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-1">
                            <p class="text-xs font-bold text-gray-800 truncate">${title}</p>
                            <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded shrink-0">${typeBadge}</span>
                        </div>
                        <p class="text-[10px] text-gray-500 truncate">${item.display_name}</p>
                    </div>
                `;

                btn.addEventListener('click', () => {
                    this.hideSearchResults();
                    if (this.elements.searchInput) {
                        this.elements.searchInput.value = title;
                    }
                    this.saveSearchHistory(title);
                    this.handleLocationSelect(item);
                });

                this.elements.searchResults.appendChild(btn);
            });

            this.elements.searchResults.classList.remove('hidden');
        }

        hideSearchResults() {
            if (this.elements.searchResults) {
                this.elements.searchResults.classList.add('hidden');
            }
        }

        /**
         * 2, 5 & 6. Handle Selected Location (Smart Zoom, Highlighting Polygon & Marker Centroid)
         */
        handleLocationSelect(item) {
            const lat = parseFloat(item.lat);
            const lng = parseFloat(item.lon);

            this.state.lat = lat;
            this.state.lng = lng;
            this.state.displayName = item.display_name ? item.display_name.split(',')[0] : 'Wilayah Terpilih';
            this.state.adminLevel = item.type || 'Wilayah';

            // Bersihkan layer polygon highlight lama
            if (this.currentBoundaryLayer && this.map) {
                this.map.removeLayer(this.currentBoundaryLayer);
                this.currentBoundaryLayer = null;
            }

            // Jika item memiliki GeoJSON polygon (Batas Wilayah)
            if (item.geojson && (item.geojson.type === 'Polygon' || item.geojson.type === 'MultiPolygon')) {
                this.state.geometryType = 'Polygon';
                this.renderBoundaryPolygon(item.geojson, this.state.displayName);
            } else if (item.geojson && (item.geojson.type === 'LineString' || item.geojson.type === 'MultiLineString')) {
                this.state.geometryType = 'LineString';
                this.renderPolylineLine(item.geojson, this.state.displayName);
            } else {
                this.state.geometryType = 'Point';
                // Smart Zoom berdasarkan tingkat administrasi
                let zoomLevel = 16;
                const type = (item.type || '').toLowerCase();
                if (type.includes('county') || type.includes('kabupaten')) zoomLevel = 11;
                else if (type.includes('subdistrict') || type.includes('kecamatan')) zoomLevel = 13;
                else if (type.includes('village') || type.includes('desa')) zoomLevel = 15;

                if (this.map) {
                    this.map.flyTo([lat, lng], zoomLevel, { duration: 1.2 });
                }
            }

            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            }

            this.reverseGeocode(lat, lng, item);
        }

        renderBoundaryPolygon(geojson, name) {
            if (!this.map) return;

            this.currentBoundaryLayer = L.geoJSON(geojson, {
                style: {
                    color: '#059669',
                    weight: 3,
                    fillColor: '#10b981',
                    fillOpacity: 0.18,
                    dashArray: '4, 4'
                }
            }).addTo(this.map);

            // Centroid Label Popup
            const bounds = this.currentBoundaryLayer.getBounds();
            const center = bounds.getCenter();

            this.currentBoundaryLayer.bindTooltip(`🏛️ Batas Wilayah: <strong>${name}</strong>`, {
                permanent: true,
                direction: 'center',
                className: 'bg-emerald-800 text-white font-bold text-[11px] px-2.5 py-1 rounded shadow-lg border-0'
            });

            // Smart Zoom fit bounds agar seluruh wilayah terlihat
            this.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 16 });

            // Hitung estimasi luas area (km²)
            try {
                if (window.turf && window.turf.area) {
                    const areaSqMeters = window.turf.area(geojson);
                    this.state.areaKm2 = (areaSqMeters / 1000000).toFixed(2);
                }
            } catch (e) {
                this.state.areaKm2 = null;
            }
        }

        renderPolylineLine(geojson, name) {
            if (!this.map) return;

            this.currentBoundaryLayer = L.geoJSON(geojson, {
                style: {
                    color: '#2563eb',
                    weight: 4,
                    opacity: 0.85
                }
            }).addTo(this.map);

            const bounds = this.currentBoundaryLayer.getBounds();
            this.map.fitBounds(bounds, { padding: [20, 20], maxZoom: 17 });
        }

        /**
         * 3. Reverse Geocoding
         */
        async reverseGeocode(lat, lng, existingData = null) {
            if (this.isGeocoding) return;
            this.isGeocoding = true;
            this.showPanelLoading(true);

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`, {
                    headers: { 'Accept-Language': 'id-ID,id;q=0.9' }
                });

                let data = null;
                if (response.ok) data = await response.json();

                const addr = data && data.address ? data.address : (existingData && existingData.address ? existingData.address : {});

                this.state.displayName = data && data.display_name ? data.display_name.split(',')[0] : (existingData ? existingData.display_name.split(',')[0] : `Titik (${lat.toFixed(5)}, ${lng.toFixed(5)})`);
                this.state.lat = lat;
                this.state.lng = lng;
                this.state.address = data && data.display_name ? data.display_name : 'Alamat tidak terdefinisi secara detail';

                this.state.village = addr.village || addr.suburb || addr.neighbourhood || addr.hamlet || 'Desa/Kelurahan Terdekat';
                this.state.subdistrict = addr.town || addr.city_district || addr.subdistrict || addr.municipality || 'Kecamatan Terdekat';
                this.state.district = addr.city || addr.county || addr.regency || 'Kabupaten Cianjur';
                this.state.province = addr.state || 'Jawa Barat';

                this.state.cleanSubdistrict = this.state.subdistrict.replace(/Kecamatan|Kec\./gi, '').trim();

                this.isGeocoding = false;
                this.showPanelLoading(false);
                this.updateInfoPanel();

                this.showToast(`📍 Wilayah terpilih: ${this.state.displayName}`, 'success');

                if (this.onLocationSelected) {
                    this.onLocationSelected(this.state);
                }
            } catch (err) {
                this.isGeocoding = false;
                this.showPanelLoading(false);
                this.state.lat = lat;
                this.state.lng = lng;
                this.state.address = `Koordinat GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                this.state.village = 'Desa Terdekat';
                this.state.subdistrict = 'Cianjur';
                this.state.district = 'Kabupaten Cianjur';
                this.state.province = 'Jawa Barat';
                this.updateInfoPanel();
            }
        }

        updateInfoPanel() {
            if (this.elements.panelName) this.elements.panelName.textContent = this.state.displayName;
            if (this.elements.panelAdminLevel) this.elements.panelAdminLevel.textContent = (this.state.adminLevel || 'Wilayah').toUpperCase();
            if (this.elements.panelArea) {
                this.elements.panelArea.textContent = this.state.areaKm2 ? `${this.state.areaKm2} km²` : '-';
            }
            if (this.elements.panelCoords) this.elements.panelCoords.textContent = `${this.state.lat.toFixed(6)}, ${this.state.lng.toFixed(6)}`;
            if (this.elements.panelAddress) this.elements.panelAddress.textContent = this.state.address;
            if (this.elements.panelVillage) this.elements.panelVillage.textContent = this.state.village;
            if (this.elements.panelSubdistrict) this.elements.panelSubdistrict.textContent = this.state.subdistrict;
            if (this.elements.panelDistrict) this.elements.panelDistrict.textContent = this.state.district;
            if (this.elements.panelProvince) this.elements.panelProvince.textContent = this.state.province;

            if (this.elements.infoPanel) {
                this.elements.infoPanel.classList.remove('hidden');
            }

            // Ambil statistik data penduduk BPS real-time untuk wilayah aktif
            if (this.state.lat && this.state.lng) {
                this.fetchWilayahStats(this.state.lat, this.state.lng);
            }
        }

        async fetchWilayahStats(lat, lng) {
            try {
                const res = await fetch(`/api/wilayah/detect?lat=${lat}&lng=${lng}`);
                if (res.ok) {
                    const json = await res.json();
                    if (json.status === 'success' && json.data) {
                        const d = json.data;
                        this.state.population = d.jumlah_penduduk;
                        this.state.kk = d.jumlah_kk;
                        this.state.density = d.kepadatan_km2;
                        this.state.densityCategory = d.kepadatan_kategori;
                        this.state.rt = d.jumlah_rt;
                        this.state.rw = d.jumlah_rw;
                        if (!this.state.areaKm2) this.state.areaKm2 = d.luas_wilayah_km2;
                        this.state.wilayahCode = d.kode_wilayah;
                        this.renderStatsDOM();
                    }
                }
            } catch (e) {}
        }

        renderStatsDOM() {
            const elPop = document.getElementById('info-panel-population');
            const elKk = document.getElementById('info-panel-kk');
            const elDen = document.getElementById('info-panel-density');
            const elRtRw = document.getElementById('info-panel-rtrw');
            const elCode = document.getElementById('info-panel-code');

            if (elPop && this.state.population) elPop.textContent = `${this.state.population.toLocaleString('id-ID')} Jiwa`;
            if (elKk && this.state.kk) elKk.textContent = `${this.state.kk.toLocaleString('id-ID')} KK`;
            if (elDen && this.state.density) elDen.textContent = `${this.state.density.toLocaleString('id-ID')} Jiwa/km² (${this.state.densityCategory})`;
            if (elRtRw && this.state.rt) elRtRw.textContent = `${this.state.rt} RT / ${this.state.rw} RW`;
            if (elCode && this.state.wilayahCode) elCode.textContent = this.state.wilayahCode;
        }

        applyLocationToForm() {
            if (!this.state.lat || !this.state.lng) {
                this.showToast('Harap pilih titik lokasi pada peta terlebih dahulu.', 'warning');
                return;
            }

            const inputLat = document.getElementById('input-latitude');
            const inputLng = document.getElementById('input-longitude');
            const inputNama = document.getElementById('input-nama-lokasi');
            const selectKecamatan = document.getElementById('select-kecamatan-form');

            if (inputLat) inputLat.value = this.state.lat.toFixed(6);
            if (inputLng) inputLng.value = this.state.lng.toFixed(6);

            if (inputNama && (!inputNama.value || inputNama.value.trim() === '')) {
                inputNama.value = `TPS3R ${this.state.village || 'Usulan Lokasi'}`;
                inputNama.dispatchEvent(new Event('input'));
            }

            if (selectKecamatan) {
                const searchKec = this.state.cleanSubdistrict || this.state.subdistrict;
                let matchedValue = '';
                for (let i = 0; i < selectKecamatan.options.length; i++) {
                    const optVal = selectKecamatan.options[i].value;
                    if (optVal && searchKec.toLowerCase().includes(optVal.toLowerCase())) {
                        matchedValue = optVal;
                        break;
                    }
                }

                if (matchedValue) {
                    selectKecamatan.value = matchedValue;
                    selectKecamatan.dispatchEvent(new Event('change'));
                }
            }

            const pageEl = document.getElementById('page-usulan-lokasi');
            if (pageEl && pageEl._x_dataStack) {
                const alpine = pageEl._x_dataStack[0];
                if (alpine) {
                    alpine.latitude = this.state.lat.toFixed(6);
                    alpine.longitude = this.state.lng.toFixed(6);
                    if (inputNama) alpine.namaLokasi = inputNama.value;
                    if (selectKecamatan && selectKecamatan.value) {
                        alpine.kecamatan = selectKecamatan.value;
                        alpine.fetchKepadatanBps(selectKecamatan.value);
                    }
                }
            }

            this.showToast('✅ Data lokasi & koordinat berhasil dikirim ke form usulan!', 'success');
        }

        /**
         * 8. Riwayat Pencarian (Search History)
         */
        saveSearchHistory(term) {
            try {
                let history = JSON.parse(localStorage.getItem(this.searchHistoryKey) || '[]');
                history = history.filter(h => h.toLowerCase() !== term.toLowerCase());
                history.unshift(term);
                if (history.length > 5) history = history.slice(0, 5);
                localStorage.setItem(this.searchHistoryKey, JSON.stringify(history));
            } catch (e) {}
        }

        loadSearchHistory() {
            try {
                return JSON.parse(localStorage.getItem(this.searchHistoryKey) || '[]');
            } catch (e) {
                return [];
            }
        }

        showSearchHistoryDropdown() {
            const history = this.loadSearchHistory();
            if (!history || history.length === 0 || !this.elements.searchResults) return;

            this.elements.searchResults.innerHTML = '';

            const header = document.createElement('div');
            header.className = 'px-3 py-1.5 bg-gray-50 text-[10px] font-bold text-gray-500 uppercase tracking-wider flex items-center justify-between border-b border-gray-100';
            header.innerHTML = '<span>🕒 Riwayat Pencarian Terakhir</span>';
            this.elements.searchResults.appendChild(header);

            history.forEach(term => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3.5 py-2 hover:bg-emerald-50 text-xs font-semibold text-gray-700 flex items-center gap-2 border-b border-gray-100 last:border-0 transition-colors';
                btn.innerHTML = `
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span class="truncate">${term}</span>
                `;
                btn.addEventListener('click', () => {
                    if (this.elements.searchInput) this.elements.searchInput.value = term;
                    this.searchLocationMultiProvider(term);
                });
                this.elements.searchResults.appendChild(btn);
            });

            this.elements.searchResults.classList.remove('hidden');
        }

        /**
         * 10. Cache LocalStorage
         */
        getCache(query) {
            try {
                const raw = localStorage.getItem(this.cachePrefix + query.toLowerCase().trim());
                if (!raw) return null;
                const parsed = JSON.parse(raw);
                if (Date.now() - parsed.timestamp < 86400000) { // Cache valid 24 jam
                    return parsed.data;
                }
            } catch (e) {}
            return null;
        }

        setCache(query, data) {
            try {
                localStorage.setItem(this.cachePrefix + query.toLowerCase().trim(), JSON.stringify({
                    timestamp: Date.now(),
                    data: data
                }));
            } catch (e) {}
        }

        showLoading(show) {
            if (this.elements.searchLoading) {
                if (show) this.elements.searchLoading.classList.remove('hidden');
                else this.elements.searchLoading.classList.add('hidden');
            }
        }

        showPanelLoading(show) {
            if (this.elements.infoPanel) {
                if (show) this.elements.infoPanel.classList.add('opacity-50');
                else this.elements.infoPanel.classList.remove('opacity-50');
            }
        }

        showToast(message, type = 'info') {
            if (!this.elements.toastContainer) return;

            const colors = {
                success: 'bg-emerald-700 text-white border-emerald-800',
                error: 'bg-red-600 text-white border-red-700',
                warning: 'bg-amber-600 text-white border-amber-700',
                info: 'bg-teal-700 text-white border-teal-800'
            };

            const toast = document.createElement('div');
            toast.className = `px-3.5 py-2.5 rounded-xl shadow-xl text-xs font-semibold border flex items-center justify-between gap-3 transition-all duration-300 transform translate-y-2 opacity-0 ${colors[type] || colors.info}`;
            toast.innerHTML = `
                <span>${message}</span>
                <button type="button" class="text-white hover:opacity-75 font-bold ml-2">&times;</button>
            `;

            toast.querySelector('button').addEventListener('click', () => toast.remove());

            this.elements.toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
    }

    window.LocationSearchManager = LocationSearchManager;

})(window, document);
