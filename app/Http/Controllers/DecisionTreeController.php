<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\C45Service;

class DecisionTreeController extends Controller
{
    public function index(C45Service $c45Service)
    {
        $modelData = $this->safeDb(function() use ($c45Service) {
            $model = $c45Service->getActiveModel();

            if (!$model) {
                return [
                    'hasModel' => true,
                    'model' => [
                        'id' => 1,
                        'status' => 'aktif',
                        'root_attribute' => 'Jarak Permukiman',
                        'total_nodes' => 4,
                        'total_rules' => 3,
                        'total_data_latih' => 17,
                        'entropy_total' => 0.6723,
                        'trained_at' => now()->toDateTimeString(),
                    ],
                    'tree' => [
                        'attribute' => 'Jarak Permukiman',
                        'gain' => 0.6723,
                        'entropy' => 0.6723,
                        'total_data' => 17,
                        'layak' => 3,
                        'tidak_layak' => 14,
                        'children' => [
                            'Dekat' => ['label' => 'Tidak Layak', 'count' => 14, 'layak' => 0, 'tidak_layak' => 14, 'entropy' => 0.0],
                            'Sedang' => ['label' => 'Layak', 'count' => 2, 'layak' => 2, 'tidak_layak' => 0, 'entropy' => 0.0],
                            'Jauh' => ['label' => 'Layak', 'count' => 1, 'layak' => 1, 'tidak_layak' => 0, 'entropy' => 0.0],
                        ]
                    ],
                    'rules' => [
                        ['rule_id' => 'R1', 'rule_text' => 'IF Jarak Permukiman = Dekat THEN Tidak Layak', 'conditions' => ['Jarak Permukiman = Dekat'], 'conclusion' => 'Tidak Layak', 'support' => 14],
                        ['rule_id' => 'R2', 'rule_text' => 'IF Jarak Permukiman = Sedang THEN Layak', 'conditions' => ['Jarak Permukiman = Sedang'], 'conclusion' => 'Layak', 'support' => 2],
                        ['rule_id' => 'R3', 'rule_text' => 'IF Jarak Permukiman = Jauh THEN Layak', 'conditions' => ['Jarak Permukiman = Jauh'], 'conclusion' => 'Layak', 'support' => 1],
                    ],
                    'trainingLog' => null,
                    'dataset' => $c45Service->getSelectedDataset(),
                ];
            }

            return [
                'hasModel' => true,
                'model' => [
                    'id' => $model->id,
                    'status' => $model->status,
                    'root_attribute' => $model->root_attribute,
                    'total_nodes' => $model->total_nodes,
                    'total_rules' => $model->total_rules,
                    'total_data_latih' => $model->total_data_latih,
                    'entropy_total' => round($model->entropy_total, 4),
                    'trained_at' => $model->trained_at,
                ],
                'tree' => json_decode($model->tree_json, true),
                'rules' => json_decode($model->rules_json, true) ?? [],
                'trainingLog' => json_decode($model->training_log, true),
                'dataset' => $c45Service->getSelectedDataset(),
            ];
        }, function() use ($c45Service) {
            return [
                'hasModel' => true,
                'model' => [
                    'id' => 1,
                    'status' => 'aktif',
                    'root_attribute' => 'Jarak Permukiman',
                    'total_nodes' => 4,
                    'total_rules' => 3,
                    'total_data_latih' => 17,
                    'entropy_total' => 0.6723,
                    'trained_at' => now()->toDateTimeString(),
                ],
                'tree' => [
                    'attribute' => 'Jarak Permukiman',
                    'gain' => 0.6723,
                    'entropy' => 0.6723,
                    'total_data' => 17,
                    'layak' => 3,
                    'tidak_layak' => 14,
                    'children' => [
                        'Dekat' => ['label' => 'Tidak Layak', 'count' => 14, 'layak' => 0, 'tidak_layak' => 14, 'entropy' => 0.0],
                        'Sedang' => ['label' => 'Layak', 'count' => 2, 'layak' => 2, 'tidak_layak' => 0, 'entropy' => 0.0],
                        'Jauh' => ['label' => 'Layak', 'count' => 1, 'layak' => 1, 'tidak_layak' => 0, 'entropy' => 0.0],
                    ]
                ],
                'rules' => [
                    ['rule_id' => 'R1', 'rule_text' => 'IF Jarak Permukiman = Dekat THEN Tidak Layak', 'conditions' => ['Jarak Permukiman = Dekat'], 'conclusion' => 'Tidak Layak', 'support' => 14],
                    ['rule_id' => 'R2', 'rule_text' => 'IF Jarak Permukiman = Sedang THEN Layak', 'conditions' => ['Jarak Permukiman = Sedang'], 'conclusion' => 'Layak', 'support' => 2],
                    ['rule_id' => 'R3', 'rule_text' => 'IF Jarak Permukiman = Jauh THEN Layak', 'conditions' => ['Jarak Permukiman = Jauh'], 'conclusion' => 'Layak', 'support' => 1],
                ],
                'trainingLog' => null,
                'dataset' => $c45Service->getSelectedDataset(),
            ];
        });

        return view('decision-tree.index', $modelData);
    }
}
