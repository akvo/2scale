<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\RsrProject;

class RsrReportController extends Controller
{
    public function generateReport(Request $r)
    {
        $partnershipId = $r->input('partnership_id');
        if ($r->input('partnership_id') === "0") {
            $partnershipId = null;
        }

        // get BSSs
        $bss = $this->getOrganizationFormData($partnershipId, 'bss');
        // get ABC cluster
        $abc_clusters = $this->getOrganizationFormData($partnershipId, 'abc_names');
        // get other partner
        $other_main_partners = $this->getOrganizationFormData($partnershipId, 'other_main_partners');
        // producer organization
        $producer_organizations = $this->getOrganizationFormData($partnershipId, 'producer_organization');


        $rsrProject = RsrProject::where('partnership_id', $partnershipId)
                        ->with(['rsr_results' => function($query) {
                            $query->orderBy('order');
                            $query->with('rsr_indicators.rsr_dimensions.rsr_dimension_values');
                            $query->with('rsr_indicators.rsr_periods.rsr_period_dimension_values');
                        }])->first();

        // capitalize
        $rsrProject['subtitle'] = $this->capitalizeAfterDelimiters($rsrProject['subtitle'], ['.', '. ']);
        $rsrProject['project_plan_summary'] = $this->capitalizeAfterDelimiters($rsrProject['project_plan_summary'], ['.', '. ']);
        $rsrProject['goals_overview'] = $this->capitalizeAfterDelimiters($rsrProject['goals_overview'], ['.', '. ']);
        $rsrProject['target_group'] = $this->capitalizeAfterDelimiters($rsrProject['target_group'], ['.', '. ']);
        // $rsrProject['background'] = $this->capitalizeAfterDelimiters($rsrProject['background'], ['.', '. ']);
        // $rsrProject['sustainability'] = $this->capitalizeAfterDelimiters($rsrProject['sustainability'], ['.', '. ']);
        // EOL capitalize

        $rsrProject['rsr_results'] = $rsrProject['rsr_results']->map(function ($res) use ($r) {
            $res['rsr_indicators'] = $res['rsr_indicators']->map(function ($ind) use ($r) {
                $ind['period_actual_sum'] = $ind['rsr_periods']->pluck('actual_value')->sum();
                if ($ind['has_dimension']) {
                    // collect dimensions value all period
                    $periodDimensionValues = $ind['rsr_periods']->map(function ($per) {
                        return $per['rsr_period_dimension_values'];
                    })->flatten(1);
                    // aggregate dimension value
                    $ind['rsr_dimensions'] = $ind['rsr_dimensions']->map(function ($dim)
                        use ($periodDimensionValues) {
                        $dim['rsr_dimension_values'] = $dim['rsr_dimension_values']->map(function ($dimVal)
                            use ($periodDimensionValues) {
                            $dimVal['period_actual_sum'] = $periodDimensionValues->where('rsr_dimension_value_id', $dimVal['id'])
                                    ->pluck('value')
                                    ->sum();
                            return $dimVal;
                        });
                        return $dim;
                    });
                }
                $ind['rsr_periods'] = $ind['rsr_periods']->filter(function ($period) use ($r) {
                    return $period['period_end'] < $r->date;
                });
                // return Arr::except($ind, 'rsr_periods');
                return $ind;
            });
            $uii = Str::before($res['title'], ': ');
            $res['uii_order'] = $this->addPrefixToContributionNameForOrdering($uii);
            $res['is_uii'] = str_contains(strtolower($res['title']), 'uii');
            return $res;
        })->sortBy('uii_order')->values()->all();
        // return $rsrProject;
        $cards = explode('|', $r->card);
        $rsrProject['bss'] = $bss->pluck('text')->unique()->values()->all();
        $rsrProject['abc_names'] = $abc_clusters->pluck('text')->unique()->values()->all();
        $rsrProject['other_main_partners'] = $other_main_partners->pluck('text')->unique()->values()->count();
        $rsrProject['producer_organization'] = $producer_organizations->pluck('text')->unique()->values()->count();
        $data = [
            "filename" => $r->input('filename'),
            "project" => $rsrProject,
            // "updates" => $this->getUpdates($rsr, $projectId),
            "updates" => [],
            "columns" => $r->input('columns'),
            "cards" => ["title" => $cards[0], "value" => $cards[1]],
            "charts" => $this->b64toImage($r),
            "titles" => $r->input('titles'),
        ];
        // return $data;
        $html = view('reports.template', ['data' => $data])->render();
        $filename = (string) Str::uuid().'.html';
        Storage::disk('public')->put('./reports/'.$filename, $html);
        return Storage::disk('public')->url('reports/'.$filename);
    }

    public function b64toImage($requests)
    {
        $base64_images = $requests->input('images');
        $files = collect();
        foreach($base64_images as $key => $image) {
            $filename = $requests->input('filename').'-'.$key.'.png';
            if (preg_match('/^data:image\/(\w+);base64,/', $image)) {
                $data = substr($image, strpos($image, ',') + 1);
                $data = base64_decode($data);
                Storage::disk('public')->put('./images/'.$filename, $data);
                $files->push($filename);
            }
        }
        return $files;
    }

    public function capitalizeAfterDelimiters($string='', $delimiters=array())
    {
        foreach ($delimiters as $delimiter) {
            $temp = explode($delimiter, $string);
            array_walk($temp, function (&$value) { $value = ucfirst($value); });
            $string = implode($delimiter, $temp);
        }
        if (empty($string) || $string == "​" || $string == null) {
            return $string;
        }
        return (Str::endsWith($string, '.')) ? $string : $string.'.';
    }

    private function getOrganizationFormData($partnershipId, $type)
    {
        $config = config('akvo-rsr.organization_form');
        $config = $config[$type];
        switch ($type) {
            case 'bss':
                $partnershipQid = $config['qids']['partnership_qid'];
                $typeQid = $config['qids']['bss_name_qid'];
                break;
            case 'abc_names':
                $partnershipQid = $config['qids']['partnership_qid'];
                $typeQid = $config['qids']['cluster_qid'];
                break;
            case 'other_main_partners':
                $partnershipQid = $config['qids']['partnership_qid'];
                $typeQid = $config['qids']['enterprise_qid'];
                break;
            case 'producer_organization':
                $partnershipQid = $config['qids']['partnership_qid'];
                $typeQid = $config['qids']['producer_organization_qid'];
                break;
            default:
                $partnershipQid = null;
                $typeQid = null;
                break;
        }
        $partnership_answers = \App\Answer::where('question_id', $partnershipQid)->get();
        if ($partnershipId) {
            $partnership = \App\Partnership::find($partnershipId);
            $partnership_answers = $partnership_answers->filter(function ($answer) use ($partnership) {
                return str_contains(strtolower($answer['text']), strtolower($partnership->name));
            });
        }
        $datapoints_answers = $partnership_answers->pluck('datapoint_id');
        $type_answers = \App\Answer::where('question_id', $typeQid)
                            ->whereIn('datapoint_id', $datapoints_answers)
                            ->get()->map(function ($item) {
                                $item['text'] = trim($item['text']);
                                return $item;
                            });
        return $type_answers;
    }

    /**
     * Function to add prefix to contribution title/name
     * for ordering purposes
     */
    private function addPrefixToContributionNameForOrdering($name)
    {
        if (str_contains($name, "Private sector contribution")) {
            // add 1 to put private contribution before 2scale contrib
            return "Y##1PSC (Euros)";
        }
        if (str_contains($name, "2SCALE's Contribution")) {
            return "Y##2SCALE contributions (Euros)";
        }
        if (str_contains($name, "IP-")) {
            return "Z##".$name;
        }
        return $name;
    }
}
