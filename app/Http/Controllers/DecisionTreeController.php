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
                    'hasModel' => false,
                    'model' => null,
                    'tree' => null,
                    'rules' => [],
                    'trainingLog' => null,
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
            ];
        }, function() {
            return [
                'hasModel' => false,
                'model' => null,
                'tree' => null,
                'rules' => [],
                'trainingLog' => null,
            ];
        });

        return view('decision-tree.index', $modelData);
    }
}
