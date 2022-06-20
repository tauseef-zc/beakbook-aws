<?php

namespace App\Http\Controllers;

use App\Models\Barn;
use App\Models\BarnStatistic;
use App\Models\Cycle;
use App\Models\Device;
use App\Models\Farm;
use App\Models\FavouriteBarns;
use App\Models\Section;
use App\Models\SectionStatistic;
use App\Models\PredictionStatistic;
use App\Models\UserBeakbook;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Jimmyjs\ReportGenerator\Facades\PdfReportFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum;

class DashboardController extends Controller
{
    /*
     * Dashboard APIs
     * API 1: Favorite Barns
     * API 2: Selected Barn Details
     *
     * [To-Do]
     * API 3: Add New cycle
     * API 4: Edit Selected Cycle Details
     * API 5: Edit Barn Overview
     * API 6: Add New Scale
     * API 7: Download CSV/ PDF
     * API 8: Add Morality Count
     * API 7: Section Position
     */

    private function checkBarnAndCycle($barnId, $cycleId)
    {
        $barnExists = Barn::where('id', $barnId)
            ->exists();

        if ($barnExists) {
            $cycleExists = Cycle::where('id', $cycleId)
                ->where('barn_id', $barnId)
                ->exists();

            if ($cycleExists) {
                return $cycleExists;
            }
            return false;
        }

        return false;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Includes: Barn Overview, Barn Analysis, Graphs, Cycle List, Selected Cycle Details
     * To-Do: Scale Overview, Graph Dropdown, Section Id in Graphs
     */
    public function dashboardApi(Request $request)
    {
        $request->validate([
            'barnId' => 'required',
            'cycleId' => 'required'
        ]);

        $barnId = $request->input('barnId');
        $cycleId = $request->input('cycleId');

        $barnCycleExists = $this->checkBarnAndCycle($barnId, $cycleId);

        if (!$barnCycleExists) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid barn id or cycle id',
            ]);
        }

        $barnAnalysis = $this->getBarnAnalysis($barnId, $cycleId);
        // $graphs = $this->getGraphs($barnId, $cycleId);
        $cycleList = $this->getCycles($barnId);
        $cycleDetails = $this->getCurrentCycleDetails($cycleId);
        $barnOverview = $this->getBarnOverview($barnId);

        return response()->json([
            'status' => true,
            'message' => 'Successfully retrieved the data',
            'barnOverview' => $barnOverview,
            'barnAnalysis' => $barnAnalysis,
            'cycleSelection' => $cycleList,
            'cycleDetails' => $cycleDetails,
        ]);
    }

    public function dashboardApiGraphs(Request $request)
    {
        $request->validate([
            'barnId' => 'required',
            'cycleId' => 'required',
            'graphName' => 'required'
        ]);

        $barnId = $request->input('barnId');
        $cycleId = $request->input('cycleId');
        $graphName = $request->input('graphName');

        $barnCycleExists = $this->checkBarnAndCycle($barnId, $cycleId);

        if (!$barnCycleExists) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid barn id or cycle id',
            ]);
        }

        $graphs = $this->getGraphs($barnId, $cycleId, $graphName);
        return response()->json([
            'status' => true,
            'message' => 'Successfully retrieved the data',
            'graphs' => $graphs
        ]);
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Section 1: Barn Analysis
     */
    private function getBarnAnalysis($barnId, $cycleId)
    {
        $elements = ['Current Weight', 'Average Growth', 'Mortality Ratio', 'Predicted Weight'];
        $output = [];

        foreach ($elements as $element) {
            $analyzedData = $this->barnAnalysisValues($element, $barnId, $cycleId);
            if ($analyzedData) {
                array_push($output, $analyzedData);
            } else {
                return false;
            }
        }
        return $output;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Section 1: Barn Analysis
     * Function: Values
     */
    private function barnAnalysisValues($element, $barnId, $cycleId)
    {

        switch ($element) {
            case 'Current Weight':
                $weight = $this->formatWeightData($this->getCurrentWeight($barnId));
                $icon = 'icon icon-weight';
                $value = number_format($weight['weight'], 2);
                $unit = $weight['unit'];
                break;

            case 'Average Growth':
                $growth = $this->formatWeightData($this->getAverageGrowth($barnId, $cycleId));
                $icon = ' icon icon-scale';
                $value = number_format($growth['weight'], 2);
                $unit = $growth['unit'] . ' / day';
                break;

            case 'Mortality Ratio':
                $icon = 'icon icon-chicken-lg';
                $value = $this->calculateDeathRatio($barnId);
                $unit = '%';
                break;

            case 'Predicted Weight':
                $averageWeight = $this->getPredictedWeight($barnId, $cycleId);
                if ($averageWeight < 1000) {
                    $preW_unit = 'gr';
                    $avWeight = $averageWeight;
                } else {
                    $preW_unit = 'kg';
                    $avWeight = $averageWeight / 1000;
                    $avWeight = number_format($avWeight, 3);
                }
                $icon = 'icon icon-scale-2';
                $value = $avWeight;
                $unit = $preW_unit . ' ' . $this->getPredictedWeightDay($barnId, $cycleId);
                break;

            default:
                return response()->json(['status' => false, 'error' => 'Invalid element'], 500);
        }

        $data = $this->barnAnalysisDataFormat($icon, $element, $value, $unit);
        return $data;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Section 1: Barn Analysis
     * Function: Response Format
     */
    private function barnAnalysisDataFormat($icon, $label, $value, $unit)
    {
        $format = [
            'icon' => $icon,
            'label' => $label,
            'value' => $value,
            'unit' => $unit
        ];

        return $format;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Section 1: Barn Analysis
     * Function: Current weight calculation [To-Do]
     */
    private function calculateCurrentWeight($barnId)
    {
        $currentWeight = BarnStatistic::select('std', 'mean')
            ->where('barn_id', $barnId)
            ->get();

        return $currentWeight;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Section 1: Barn Analysis
     * Function: Death Ration Calculation [To-Do]
     */
    private function calculateDeathRatio($barnId)
    {
        $noOfDeaths = Cycle::where('barn_id', $barnId)->get();
        $death_count = $noOfDeaths->sum('death_number');
        $population_number = $noOfDeaths->sum('population_number');

        $deathRatio = ($death_count / $population_number) * 100;

        return number_format($deathRatio, 2);
    }

    /*
     * Get current weight of the barn
     * @param $barnId
     * @param $cycleId
     */
    private function getCurrentWeight($barnId)
    {
        $currentWeight = BarnStatistic::where('barn_id', $barnId)
            ->orderBy('timestamp', 'desc')
            ->value('mean');
        return $currentWeight;
    }

    /*
     * Get average growth of the barn
     * @param $barnId
     * @param $cycleId
     */
    private function getAverageGrowth($barnId, $cycleId)
    {
        $averageGrowth = BarnStatistic::where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->orderBy('timestamp', 'desc')
            ->get();
        if ($averageGrowth->count() > 1) {
            $mean_today = $averageGrowth[0]->mean;
            $mean_prv_day = $averageGrowth[1]->mean;

            $avg_growth = $mean_today - $mean_prv_day;
        } else {
            $avg_growth = 0;
        }
        return $avg_growth;
    }

    /*
    *format weight data
    */
    private function formatWeightData($weight)
    {
        if ($weight < 1000) {
            $weight = $weight;
            $Weight_unit = 'gr';
        } else {
            $weight = $weight / 1000;
            $Weight_unit = 'kg';
        }

        return [
            'weight' => $weight,
            'unit' => $Weight_unit
        ];
    }



    /*
    * Dashboard API 2: Selected Barn Details
    * Section 1: Barn Analysis
    * Function: Get Latest Predictions
    */
    private function getPredictedStats($barnId, $cycleId)
    {
        $predictionStats = PredictionStatistic::where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->orderBy('day', 'DESC')
            ->select('prediction', 'day')
            ->first();

        if ($predictionStats == null) {
            return 0;
        } else {
            return $predictionStats;
        }
    }

    /*
    * Dashboard API 2: Selected Barn Details
    * Section 1: Barn Analysis
    * Function: Get Latest Predicted Weight
    */
    private function getPredictedWeight($barnId, $cycleId)
    {
        $predictionStats = $this->getPredictedStats($barnId, $cycleId);

        if ($predictionStats) {
            $predictedWeight = $predictionStats->prediction;
            return $predictedWeight;
        }
        return 0;
    }

    /*
    * Dashboard API 2: Selected Barn Details
    * Section 1: Barn Analysis
    * Function: Get Latest Prediction Day
    */
    private function getPredictedWeightDay($barnId, $cycleId)
    {
        $predictionStats = $this->getPredictedStats($barnId, $cycleId);

        if ($predictionStats) {
            $day = $predictionStats->day;
            $dayLabel = '-' . ' ' . 'Day' . ' ' . $day;
            return $dayLabel;
        }
        return null;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Section 2: Barn Overview
     */
    private function getBarnOverview($barnId)
    {
        $barnOverview = Barn::where('id', $barnId)
            ->with('farm')
            ->get();

        if ($barnOverview) {
            return  $barnOverview;
        }
        return false;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Section 4.1: Get Selected Cycle Details
     */
    private function getCurrentCycleDetails($cycleId)
    {
        $cycleDetails = Cycle::find($cycleId);

        if ($cycleId) {
            return $cycleDetails;
        }
        return  false;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Cycle List Dropdown
     */
    private function getCycles($barnId)
    {
        $cycleIds = Cycle::where('barn_id', $barnId)
            ->pluck('id');
        $labelNo = 0;
        $output = [];

        foreach ($cycleIds as $cycleId) {
            $labelNo = $labelNo + 1;
            $startDate = $this->getCycleStartDate($cycleId);
            $cycleOverview = $this->getCycleOverview($barnId, $cycleId);
            $cycleData = $this->cycleDataFormat($cycleId, $startDate, $labelNo, $cycleOverview);
            if ($cycleData) {
                array_push($output, $cycleData);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Could not find analyzed data',
                ], 422);
            }
        }
        return $output;
    }

    private function getCycleStartDate($cycleId)
    {
        $date = Cycle::where('id', $cycleId)
            ->value('starting_date');
        return $date;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Cycle List Dropdown
     * Function: Response Format
     */
    private function cycleDataFormat($cycleId, $date, $labelNo, $cycleOverview)
    {
        $format = [
            'label' => $date . ', ' . 'Cycle' . ' ' . $labelNo,
            'cycleId' => $cycleId,
            'cycleOverview' => $cycleOverview
        ];

        return $format;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Section 5, 6, 7: Graphs
     */
    private function getGraphs($barnId, $cycleId, $graphName)
    {

        $graphData = $this->graphProperties($barnId, $cycleId, $graphName);
        if ($graphData) {
            return $graphData;
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Could not find graph data',
            ], 422);
        }
    }

    /*
      * Dashboard API 2: Selected Barn Details
      * Section 5, 6, 7: Graphs
      * Function: Get chart type
      */
    private function getChartType($graphName)
    {
        if ($graphName == 'Total Activity') {
            $chartType = 'bar';
        } elseif ($graphName == 'Average Weight') {
            $chartType = 'area';
        } else {
            $chartType = 'line';
        }
        return $chartType;
    }

    /*
     * Dashboard API 2: Selected Barn Details
     * Section 5, 6, 7: Graphs
     * Function: Graph Properties (xAxi/ yAxis)
     */
    private function graphProperties($barnId, $cycleId, $graphName)
    {
        switch ($graphName) {
            case 'Total Activity':
                $yAxis = 'activity';
                $xLabel = 'AGE';
                $yLabelBarn = 'ACTIVITY(DAY)';
                $yLabelSection = 'ACTIVITY(DAY)/SECTION';
                break;
            case 'Average Weight':
                $yAxis = 'mean';
                $xLabel = 'AGE';
                $yLabelBarn = 'WEIGHT(GRAMS)';
                $yLabelSection = 'WEIGHT(GRAMS)/SECTION';
                break;
            case 'Standard Deviation':
                $yAxis = 'std';
                $xLabel = 'AGE';
                $yLabelBarn = 'DEVIATION/BARN';
                $yLabelSection = 'DEVIATION/SECTION';
                break;
            default:
                return null;
        }

        $chartType = $this->getChartType($graphName);
        $barnGraphData = $this->getBarnGraphData($barnId, $cycleId, $yAxis);
        $sectionGraphs = $this->getSectionGraphs($barnId, $cycleId, $yAxis);


        return $this->graphsDataFormat($graphName, $chartType, $cycleId, $xLabel, $yLabelBarn, $yLabelSection, $barnGraphData, $sectionGraphs);
    }

    private function getBarnGraphData($barnId, $cycleId, $yAxis)
    {
        $dateDif = $this->getDateDifference($barnId, $cycleId);
        $dates = $this->getDatesArray($dateDif, $barnId, $cycleId);
        $dateCount = 0;
        $output = [];

        foreach ($dates as $date) {
            $dateCount = $dateCount + 1;
            $age = $this->getAgeInDays($barnId, $cycleId, $dateCount);
            $time = $this->getMainGraphTime($barnId, $cycleId, $date);
            $value = $this->getMainGraphValue($barnId, $cycleId, $date, $time, $yAxis);
            $toDate = Carbon::parse($date)->addHours(23);
            $miniGraph = $this->miniGraphData($barnId, $cycleId, $date, $toDate, $yAxis);
            $barnGraphs = $this->barnGraphDataFormat($date, $age, $time, $value, $miniGraph);

            if ($barnGraphs) {
                array_push($output, $barnGraphs);
            } else {
                return null;
            }
        }
        return $output;
    }

    /*
    * Dashboard API 2: Selected Barn Details
    * Section 5, 6, 7: Graphs
    * Function: Response Format
    */
    private function graphsDataFormat($graphName, $chartType, $cycleId, $xLabel, $yLabelBarn, $yLabelSection, $barnGraph, $allSections)
    {
        $graphData = [
            'graphName' => $graphName,
            'chartType' => $chartType,
            'cycleId' => $cycleId,
            'xLabel' => $xLabel,
            'yLabelBarn' => $yLabelBarn,
            'yLabelSection' => $yLabelSection,
            'barnGraph' => $barnGraph,
            'allSections' => $allSections
        ];
        return $graphData;
    }

    private function  barnGraphDataFormat($date, $age, $time, $value, $miniGraph)
    {
        $format = [
            'date' => $date,
            'age' => $age,
            'time' => $time,
            'value' => $value,
            'miniGraph' => $miniGraph,
        ];
        return $format;
    }

    /*
     * Dashboard API 1: Favourite Barns
     * Includes: Favourite Barns & Cycle Ids, Cycle Selection, Barn Overview, Other Statistics
     * To-Do: Cycle Selection, Barn Overview
     */
    public function dashboardApiOne()
    {
        $userId = Auth::user()->id;
        $barnOverview = $this->favBarnOverview($userId);

        $otherStatistics = $this->otherStatistics($userId);


        return response()->json([
            'status' => true,
            'message' => 'Successfully retrieved data',
            'userId' => $userId,
            'barnOverview' => $barnOverview,
            'otherStatistics' => $otherStatistics
        ]);
    }

    /*
     * Dashboard API 1: Favourite Barns
     * Section: Favourite Barn Details
     * Function: Get Favourite Barn Ids
     * To-Do: Get User Id
     */
    private function getFavouriteBarnIds($userId)
    {
        $favBarns = FavouriteBarns::where('user_id', $userId)->pluck('barn_id');

        if ($favBarns) {
            return $favBarns;
        }
        return false;
    }

    /*
     * Dashboard API 1: Favourite Barns
     * Section: Other Statistics
     * Function: Other Statistics of the Selected Cycle
     */
    private function otherStatistics($userId)
    {
        $companyId = UserBeakbook::where('id', $userId)
            ->value('company_id');

        //temparary fix for company id - 4
        $farmId = Farm::where('company_id', 4)
            // ->where('manager_user_id', $userId)
            ->value('id');

        $barnIds = Barn::where('farm_id', $farmId)
            ->pluck('id');

        $favBarnIds = $this->getFavouriteBarnIds($userId);
        // $favBarnStats = $this->favBarnsStats($favBarnIds);
        $allBarnStats = $this->allBarnsStats($barnIds);
        // $otherStatistics = $this->otherStatisticFormat($farmId, $barnIds, $allBarnStats, $favBarnStats);
        $otherStatistics = $this->otherStatisticFormat($farmId, $barnIds, $allBarnStats);

        return $otherStatistics;
    }

    /*
     * Dashboard API 1: Favourite Barns
     * Section: Other Statistics
     * Function: Get all barns statistics
     */
    private function allBarnsStats($barnIds)
    {

        $countLabel = 'Registered Barns';
        $barnsCount  = count($barnIds);

        return $this->otherStats($barnIds, $barnsCount, $countLabel);
    }

    /*
     * Dashboard API 1: Favourite Barns
     * Section: Other Statistics
     * Function: Function: Get all barns statistics
     */
    // private function favBarnsStats($barnIds)
    // {
    //     $countLabel = 'Favourite Barns';
    //     $favBarnsCount  = count($barnIds);

    //     return $this->otherStats($barnIds, $favBarnsCount, $countLabel);
    // }

    /*
     * Dashboard API 1: Favourite Barns
     * Section: Other Statistics
     * Functions: Get Values
     */
    private function otherStats($barnIds, $barnCount, $countLabel)
    {

        $elements = ['Total Chicken', 'Active Scales', 'Registered Barns'];
        $output = [];

        foreach ($elements as $element) {
            $analyzedData = $this->otherStatsValues($element, $barnIds, $barnCount, $countLabel);
            if ($analyzedData) {
                array_push($output, $analyzedData);
            } else {
                return false;
            }
        }
        return $output;
    }

    private function otherStatsValues($element, $barnIds, $barnsCount, $countLabel)
    {

        switch ($element) {
            case 'Total Chicken':
                $imageUrl = 'Sample URL';
                $label = $element;
                $value = $this->getTotalChicken($barnIds);
                break;

            case 'Active Scales':
                $imageUrl = 'Sample URL';
                $label = $element;
                $value = $this->getActiveScales($barnIds);
                break;

            case 'Registered Barns':
                $imageUrl = 'Sample URL';
                $label = $countLabel;
                $value = $barnsCount;
                break;

            default:
                return false;
        }
        $data = $this->otherStatsFormat($imageUrl, $label, $value);
        return $data;
    }

    /*
     * Dashboard API 1: Favourite Barns
     * Section: Other Statistics
     * Functions: Get total chicken for barns
     */
    private function getTotalChicken($barnIds)
    {

        $totalChickens = 0;

        foreach ($barnIds as $barnId) {
            $cycleIds = $this->getCycleIds($barnId);
            foreach ($cycleIds as $cycleId) {
                $population = Cycle::where('id', $cycleId)->value('population_number');
                $totalChickens = $totalChickens + $population;
            }
        }
        return $totalChickens;
    }

    /*
     * Dashboard API 1: Favourite Barns
     * Section: Other Statistics
     * Functions: Get total active scales for barns
     */
    private function getActiveScales($barnIds)
    {

        $activeScaleCount = 0;


        $activeScaleIds = Device::whereIn('barn_id', $barnIds)
            ->where('is_connected', 1)
            ->pluck('id');
        $activeScaleCount = count($activeScaleIds);
        return $activeScaleCount;
    }

    /*
     * Dashboard API 1: Favourite Barns
     * Section: Other Statistics
     * Functions: Response Format
     */
    // private function otherStatisticFormat($farmId, $barnIds, $allBarns, $favBarns)
    private function otherStatisticFormat($farmId, $barnIds, $allBarns)
    {
        $format = [
            'farmId' => $farmId,
            'barnIds' => $barnIds,
            'statistics' => [
                'allBarns' => $allBarns,
                // 'favBarns' => $favBarns
            ]
        ];
        return $format;
    }

    private function otherStatsFormat($icon, $label, $value)
    {
        $format = [
            'icon' => $icon,
            'label' => $label,
            'value' => $value
        ];
        return $format;
    }

    /*
    * Dashboard API 1: Favourite Barns
    * Section: Cycle Overview
    */
    private function getCycleOverview($barnId, $cycleId)
    {

        $averageWeight = BarnStatistic::where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->OrderBy('timestamp', 'desc')
            ->value('mean');

        $currentAge = $this->getCurrentAgeInDays($barnId, $cycleId);

        $breed = Cycle::where('barn_id', $barnId)
            ->where('id', $cycleId)
            ->value('breed');

        $population = Cycle::where('barn_id', $barnId)
            ->where('id', $cycleId)
            ->value('population_number');

        return $this->cycleOverviewFormat($averageWeight, $currentAge, $population, $breed);
    }

    /*
     * Dashboard API 1: Favourite Barns
     * Section: Cycle Overview
     * Function: Response Format
     */
    private function cycleOverviewFormat($averageWeight, $currentAge, $population, $breed)
    {
        if ($averageWeight < 1000) {
            $unit = 'gr';
            $avWeight = $averageWeight;
        } else {
            $unit = 'kg';
            $avWeight = $averageWeight / 1000;
        }
        $format = [
            'averageWeight' => $avWeight,
            'averageWeightUnit' => $unit,
            'currentAge' => $currentAge,
            'currentAgeUnit' => 'days old',
            'population' => $population,
            'breed' => $breed
        ];

        return $format;
    }

    public function favBarnOverview($userId)
    {
        $favBarnIds = $this->getFavouriteBarnIds($userId);


        $output = [];
        $labelNo = 0;

        foreach ($favBarnIds as $barnId) {

            $cycleId = $this->getCycleId($barnId);
            if ($cycleId) {
                $cycleOverview = $this->getCycleOverview($barnId, $cycleId);

                $isActive = $this->isCycleActive($cycleId);
                $labelNo = $labelNo + 1;

                $barnName = $this->barnName($barnId);

                $barnOverview = $this->favCycleOverviewFormat($barnId, $barnName, $cycleId, $isActive, $cycleOverview);

                if ($barnOverview) {
                    array_push($output, $barnOverview);
                }
            }
        }

        return $output;
    }

    private function barnName($barnId)
    {
        $name = Barn::where('id', $barnId)
            ->value('name');
        return $name;
    }

    private function getCycleId($barnId)
    {
        $activeCycle = $this->getActiveCycle($barnId);

        if (!$activeCycle) {
            $previousCycle = $this->getPreviousCycle($barnId);
            return $previousCycle;
        }
        return $activeCycle;
    }

    private function getActiveCycle($barnId)
    {
        $activeCycleId = Cycle::where('is_active', 1)
            ->where('barn_id', $barnId)
            ->value('id');

        if ($activeCycleId) {
            return $activeCycleId;
        }
        return null;
    }

    private function getPreviousCycle($barnId)
    {
        $previousCycle = Cycle::where('is_active', 0)
            ->where('barn_id', $barnId)
            ->orderBy('end_date')
            ->select('id')
            ->first();

        if ($previousCycle) {
            $previousCycleId = $previousCycle->id;
            return $previousCycleId;
        }
        return null;
    }

    private function favCycleOverviewFormat($barnId, $barnName, $cycleId, $isActive, $cycleOverview)
    {
        $format = [
            'barnId' => $barnId,
            'label' => $barnName,
            'cycleId' => $cycleId,
            'isActive' => $isActive,
            'cycleOverview' => $cycleOverview
        ];

        return $format;
    }

    private function isCycleActive($cycleId)
    {
        $isActive = Cycle::where('id', $cycleId)
            ->value('is_active');

        if ($isActive) {
            return true;
        }
        return false;
    }

    public function getCycleIds($barnId)
    {
        $cycleIds = Cycle::where('barn_id', $barnId)->pluck('id');
        return $cycleIds;
    }

    private function getCurrentAgeInDays($barnId, $cycleId)
    {
        $startingAge = Cycle::where('id', $cycleId)
            ->where('barn_id', $barnId)
            ->value('starting_age');

        $dateDifference = $this->getDateDifference($barnId, $cycleId);

        $currentAge = $startingAge + $dateDifference;

        return $currentAge;
    }

    private function getDateDifference($barnId, $cycleId)
    {

        $cycleData = Cycle::where('id', $cycleId)
            ->where('barn_id', $barnId)
            ->select('starting_date', 'is_active', 'end_date')
            ->get();

        $startDate = $cycleData[0]->starting_date;
        $endDate = $cycleData[0]->end_date;
        $isActive = $cycleData[0]->is_active;

        $currentDate = Carbon::now();
        $formattedStartDate = Carbon::parse($startDate);
        $formattedEndDate = Carbon::parse($endDate);

        if ($isActive === 1) {
            $dateDifference = $formattedStartDate->diffInDays($currentDate);
            return $dateDifference;
        }

        $dateDifference = $formattedStartDate->diffInDays($formattedEndDate);
        return $dateDifference;
    }

    private function getMainGraphTime($barnId, $cycleId, $date)
    {
        $formattedDate = Carbon::parse($date);
        $toDate = Carbon::parse($date)->addHours(23);
        $timestamp = BarnStatistic::where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->whereBetween('timestamp', [$formattedDate, $toDate])
            ->orderBy('timestamp', 'ASC')
            ->first('timestamp');

        $decode = json_decode($timestamp, true);

        if (!isset($decode['timestamp'])) {
            return null;
        }

        $timestampValue = $decode['timestamp'];
        $time = date('H:i:s', strtotime(Carbon::parse($timestampValue)));

        return $time;
    }

    private function getMainGraphValue($barnId, $cycleId, $date, $time, $valueLabel)
    {
        $timestamp = $date . ' ' . $time;
        $value = BarnStatistic::where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->where('timestamp', $timestamp)
            ->value($valueLabel);

        if ($value) {
            return $value;
        }
        return null;
    }

    private function getMainGraphData($barnId, $cycleId, $yAxis)
    {
        $dateDif = $this->getDateDifference($barnId, $cycleId);
        $dates = $this->getDatesArray($dateDif, $barnId, $cycleId);
        $dateCount = 0;
        $output = [];

        foreach ($dates as $date) {
            $dateCount = $dateCount + 1;
            $age = $this->getAgeInDays($barnId, $cycleId, $dateCount);
            $time = $this->getMainGraphTime($barnId, $cycleId, $date);
            $value = $this->getMainGraphValue($barnId, $cycleId, $date, $time, $yAxis);
            $toDate = Carbon::parse($date)->addHours(23);
            $miniGraph = $this->miniGraphData($barnId, $cycleId, $date, $toDate, $yAxis);
            $mainGraphs = $this->mainGraphFormat($date, $age, $time, $value, $miniGraph);

            if ($mainGraphs) {
                array_push($output, $mainGraphs);
            } else {
                return null;
            }
        }
        return $output;
    }

    private function getDatesArray($dateDifference, $barnId, $cycleId)
    {
        $cycleStartDate = Cycle::where('id', $cycleId)
            ->where('barn_id', $barnId)
            ->value('starting_date');

        $formattedStartDate = Carbon::parse($cycleStartDate);

        $output = [];
        array_push($output, $formattedStartDate->format('Y-m-d'));

        for ($count = 1; $count <= $dateDifference; $count++) {
            $date = $formattedStartDate->addDay()->format('Y-m-d');
            array_push($output, $date);
        }
        return $output;
    }

    private function getMiniGraphValue($barnId, $cycleId, $date, $time, $value)
    {
        $dateTime = $date . ' ' . $time;
        $graphData = BarnStatistic::where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->where('timestamp', $dateTime)
            ->value($value);

        return $graphData;
    }

    private function getDayGraphData($barnId, $cycleId, $fromDate, $toDate, $value)
    {
        $graphData = BarnStatistic::where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->whereBetween('timestamp', [$fromDate, $toDate])
            ->pluck($value);

        return $graphData;
    }

    private function getDayGraphTime($barnId, $cycleId, $fromDate, $toDate)
    {
        $dateTime = BarnStatistic::where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->whereBetween('timestamp', [$fromDate, $toDate])
            ->pluck('timestamp');

        $output = [];

        foreach ($dateTime as $dt) {
            $time = date('H:i:s', strtotime($dt));
            if ($time) {
                array_push($output, $time);
            } else {
                return null;
            }
        }
        return $output;
    }

    private function getMiniGraphAll($barnId, $cycleId)
    {
        $dateDifference = $this->getDateDifference($barnId, $cycleId);
        $dates = $this->getDatesArray($dateDifference, $barnId, $cycleId);
        $dateCount = 0;
        $output = [];

        foreach ($dates as $date) {
            $formattedDate = Carbon::parse($date)->format('Y-m-d');
            $toDate = Carbon::parse($date)->addHours(23);
            $dateCount = $dateCount + 1;
            $age = $this->getAgeInDays($barnId, $cycleId, $dateCount);
            $value = $this->getDayGraphData($barnId, $cycleId, $date, $toDate, 'mean');
            $time = $this->getDayGraphTime($barnId, $cycleId, $date, $toDate);
            $miniGraphFormatTwo = $this->graphFormat($formattedDate, $age, $time, $value);

            if ($miniGraphFormatTwo) {
                array_push($output, $miniGraphFormatTwo);
            } else {
                return null;
            }
        }
        return $output;
    }

    private function miniGraphData($barnId, $cycleId, $date, $toDate, $yAxis)
    {
        $times = $this->getDayGraphTime($barnId, $cycleId, $date, $toDate);
        $output = [];

        foreach ($times as $time) {
            $value = $this->getMiniGraphValue($barnId, $cycleId, $date, $time, $yAxis);
            //            $convertedTime = $this->covertTimezoneToLocation($time);
            $miniGraph = $this->graphFormat2($time, $value);

            if ($miniGraph) {
                array_push($output, $miniGraph);
            } else {
                return null;
            }
        }
        return $output;
    }

    private function mainGraphFormat($date, $age, $time, $value, $miniGraph)
    {
        $format = [
            'date' => $date,
            'age' => $age,
            'time' => $time,
            'value' => $value,
            'miniGraph' => $miniGraph,
        ];
        return $format;
    }

    private function graphFormat($date, $age, $time, $value)
    {
        $format = [
            'date' => $date,
            'age' => $age,
            'time' => $time,
            'value' => $value
        ];
        return $format;
    }

    private function graphFormat2($time, $value)
    {
        $format = [
            'time' => $time,
            'value' => $value
        ];
        return $format;
    }

    private function getAgeInDays($barnId, $cycleId, $dateCount)
    {
        $startingAge = Cycle::where('id', $cycleId)
            ->where('barn_id', $barnId)
            ->value('starting_age');

        $age = $startingAge + $dateCount;

        return $age;
    }

    private function getSectionGraphs($barnId, $cycleId, $yAxis)
    {
        $sectionIds = $this->getSectionIds($barnId);
        $output = [];

        foreach ($sectionIds as $sectionId) {
            $sectionName = $this->getSectionName($sectionId);
            $sectionData = $this->getSectionGraphData($barnId, $sectionId, $cycleId, $yAxis);
            $sectionGraphs = $this->allSectionsFormat($sectionName, $sectionId, $sectionData);

            if ($sectionGraphs) {
                array_push($output, $sectionGraphs);
            } else {
                return null;
            }
        }
        return $output;
    }

    private function miniGraphFormat($time, $value)
    {
        $format = [
            'time' => $time,
            'value' => $value
        ];
        return $format;
    }

    private function getSectionIds($barnId)
    {
        $sectionIds = Section::where('barn_id', $barnId)
            ->pluck('id');
        return $sectionIds;
    }

    private function getSectionName($sectionId)
    {
        $name = Section::where('id', $sectionId)
            ->value('name');

        if ($name) {
            return $name;
        }
        return null;
    }

    private function getSectionGraphData($barnId, $sectionId, $cycleId, $yAxis)
    {

        $dateDif = $this->getDateDifference($barnId, $cycleId);

        $dates = $this->getDatesArray($dateDif, $barnId, $cycleId);
        $dateCount = 0;
        $output = [];

        foreach ($dates as $date) {
            $dateCount = $dateCount + 1;
            $age = $this->getAgeInDays($barnId, $cycleId, $dateCount);
            $time = $this->getSectionGraphTime($sectionId, $cycleId, $date);
            $value = $this->getSectionGraphValue($sectionId, $cycleId, $date, $time, $yAxis);
            $toDate = Carbon::parse($date)->addHours(23);
            $miniGraph = $this->sectionMiniGraphData($sectionId, $cycleId, $date, $toDate, $yAxis);
            $barnGraphs = $this->barnGraphDataFormat($date, $age, $time, $value, $miniGraph);

            if ($barnGraphs) {
                array_push($output, $barnGraphs);
            } else {
                return null;
            }
        }
        return $output;
    }

    private function allSectionsFormat($sectionName, $sectionId, $data)
    {
        $format = [
            'sectionName' => $sectionName,
            'sectionId' => $sectionId,
            'data' => $data
        ];
        return $format;
    }

    private function getSectionGraphTime($sectionId, $cycleId, $date)
    {
        $fromDate = Carbon::parse($date);
        $toDate = Carbon::parse($date)->addHours(23);
        $timestamp = SectionStatistic::where('section_id', $sectionId)
            ->where('cycle_id', $cycleId)
            ->whereBetween('timestamp', [$fromDate, $toDate])
            ->orderBy('timestamp', 'ASC')
            ->first('timestamp');

        $decode = json_decode($timestamp, true);

        if (!isset($decode['timestamp'])) {
            return null;
        }

        $timestampValue = $decode['timestamp'];
        $time = date('H:i:s', strtotime(Carbon::parse($timestampValue)));
        return $time;
    }

    private function getSectionGraphValue($sectionId, $cycleId, $date, $time, $valueLabel)
    {
        $timestamp = $date . ' ' . $time;
        $value = SectionStatistic::where('section_id', $sectionId)
            ->where('cycle_id', $cycleId)
            ->where('timestamp', $timestamp)
            ->value($valueLabel);

        if ($value) {
            return $value;
        }
        return null;
    }

    private function sectionMiniGraphData($sectionId, $cycleId, $date, $toDate, $yAxis)
    {
        $times = $this->getDayGraphTimeSection($sectionId, $cycleId, $date, $toDate);
        $output = [];

        foreach ($times as $time) {
            $value = $this->getSectionMiniGraphValue($sectionId, $cycleId, $date, $time, $yAxis);
            //            $convertedTime = $this->covertTimezoneToLocation($time);
            $miniGraph = $this->miniGraphFormat($time, $value);

            if ($miniGraph) {
                array_push($output, $miniGraph);
            } else {
                return null;
            }
        }
        return $output;
    }

    private function getDayGraphTimeSection($sectionId, $cycleId, $fromDate, $toDate)
    {
        $dateTime = SectionStatistic::where('section_id', $sectionId)
            ->where('cycle_id', $cycleId)
            ->whereBetween('timestamp', [$fromDate, $toDate])
            ->pluck('timestamp');

        $output = [];

        foreach ($dateTime as $dt) {
            $time = date('H:i:s', strtotime($dt));
            if ($time) {
                array_push($output, $time);
            } else {
                return null;
            }
        }
        return $output;
    }

    private function getSectionMiniGraphValue($sectionId, $cycleId, $date, $time, $value)
    {
        $dateTime = $date . ' ' . $time;
        $graphData = SectionStatistic::where('section_id', $sectionId)
            ->where('cycle_id', $cycleId)
            ->where('timestamp', $dateTime)
            ->value($value);

        return $graphData;
    }

    public function download(Request $request)
    {
        $request->validate([
            'userId' => 'required',
            'barnId' => 'required',
            'cycleId' => 'required',
            'fileType' => 'required',
            'downloadType' => 'required',
        ]);

        $userId = $request->input('userId');
        $barnId = $request->input('barnId');
        $cycleId = $request->input('cycleId');
        $fileType = $request->input('fileType');
        $downloadType = $request->input('downloadType');

        $urlPDF = env('APP_DOMAIN') . '/api/dashboard/download/pdf?barnId=' . $barnId . '&cycleId=' . $cycleId . '&downloadType=' . $downloadType;
        $urlCSV = env('APP_DOMAIN') . '/api/dashboard/download/csv?barnId=' . $barnId . '&cycleId=' . $cycleId . '&downloadType=' . $downloadType;

        if ($fileType == 'PDF' || $fileType == 'Pdf' || $fileType == 'pdf') {
            return response()->json([
                'status' => true,
                'userId' => $userId,
                'data' => [
                    'barnId' => $barnId,
                    'cycled' => $cycleId,
                    'url' => $urlPDF
                ]
            ]);
        } else {
            return response()->json([
                'status' => true,
                'userId' => $userId,
                'data' => [
                    'barnId' => $barnId,
                    'cycled' => $cycleId,
                    'url' => $urlCSV
                ]
            ]);
        }
    }

    public function downloadPDF(Request $request)
    {
        $request->validate([
            'barnId' => 'required',
            'cycleId' => 'required',
            'downloadType' => 'required',
        ]);

        $barnId = $request->input('barnId');
        $cycleId = $request->input('cycleId');
        $downloadType = $request->input('downloadType');
        $meta = [];

        $barnName = $this->barnName($barnId);

        $cycleStartDate = Cycle::where('id', $cycleId)
            ->where('barn_id', $barnId)
            ->value('starting_date');

        if ($downloadType == 'BARN' || $downloadType == 'Barn' || $downloadType == 'barn') {
            $pdfObject = $this->barnPdf($barnId, $cycleId);
            $title = 'Barn Statistics';
        } else {
            $pdfObject = $this->sectionPdf($barnId, $cycleId);
            $title = 'Section Statistics';
        }

        $columns = $this->pdfColumns($downloadType);

        $fileName = '[' . $cycleStartDate . ']' . ' ' . $barnName;
        return PdfReportFacade::of($title, $meta, $pdfObject, $columns)
            ->setOrientation('landscape')
            ->showMeta(false)
            // ->editColumns(['Average Weight', 'Total Activity', 'Standard Deviation'], [
            //     'class' => 'right'
            // ])
            ->download($fileName);
    }

    private function barnPdf($barnId, $cycleId)
    {
        $query = BarnStatistic::select('cycle_id', 'barn_id', 'timestamp', 'mean', 'std', 'activity')
            ->where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->orderBy('timestamp');

        if ($query) {
            return $query;
        }
        return null;
    }

    private function sectionPdf($barnId, $cycleId)
    {
        $query = SectionStatistic::join('Section', 'SectionStatistic.section_id', '=', 'Section.id')
            ->where('Section.barn_id', $barnId)
            ->where('SectionStatistic.cycle_id', $cycleId)
            ->select([
                'Section.barn_id',
                'SectionStatistic.section_id',
                'SectionStatistic.cycle_id',
                'SectionStatistic.timestamp',
                'SectionStatistic.mean',
                'SectionStatistic.std',
                'SectionStatistic.activity'
            ]);

        if ($query) {
            return $query;
        }
        return null;
    }

    private function pdfColumns($downloadType)
    {
        if ($downloadType == 'BARN' || $downloadType == 'Barn' || $downloadType == 'barn') {
            $columns = [
                'Barn Id' => 'barn_id',
                'Cycle Id' => 'cycle_id',
                'Date & Time' => 'timestamp',
                'Average Weight' => 'mean',
                'Total Activity' => 'activity',
                'Standard Deviation' => 'mean',
            ];
        } else {
            $columns = [
                'Barn Id' => 'barn_id',
                'Section Id' => 'section_id',
                'Cycle Id' => 'cycle_id',
                'Date & Time' => 'timestamp',
                'Average Weight' => 'mean',
                'Total Activity' => 'activity',
                'Standard Deviation' => 'mean',
            ];
        }
        return $columns;
    }

    public function downloadCSV(Request $request)
    {
        $request->validate([
            'barnId' => 'required',
            'cycleId' => 'required',
            'downloadType' => 'required',
        ]);

        $barnId = $request->input('barnId');
        $cycleId = $request->input('cycleId');
        $downloadType = $request->input('downloadType');

        $barnName = $this->barnName($barnId);

        $cycleStartDate = Cycle::where('id', $cycleId)
            ->where('barn_id', $barnId)
            ->value('starting_date');

        if ($downloadType == 'BARN' || $downloadType == 'Barn' || $downloadType == 'barn') {
            $csvObject = $this->barnCSV($barnId, $cycleId);
            $columns = $this->csvColumnsBarn();
        } else {
            $csvObject = $this->sectionCSV($barnId, $cycleId);
            $columns = $this->csvColumnsSection();
        }

        $updatedObject = $this->getTimeRange($csvObject);

        $fileName = '[' . $cycleStartDate . ']' . ' ' . $barnName . '.csv';
        return $this->arrayToCsv($columns, $updatedObject->toArray(), $fileName);
    }

    private function barnCSV($barnId, $cycleId)
    {
        $query = BarnStatistic::select('cycle_id', 'barn_id', 'timestamp', 'mean', 'std', 'activity')
            ->where('barn_id', $barnId)
            ->where('cycle_id', $cycleId)
            ->orderBy('timestamp')
            ->get();

        if ($query) {
            return $query;
        }
        return null;
    }

    private function sectionCSV($barnId, $cycleId)
    {
        $query = SectionStatistic::join('Section', 'SectionStatistic.section_id', '=', 'Section.id')
            ->where('Section.barn_id', $barnId)
            ->where('SectionStatistic.cycle_id', $cycleId)
            ->get([
                'Section.barn_id',
                'SectionStatistic.section_id',
                'SectionStatistic.cycle_id',
                'SectionStatistic.timestamp',
                'SectionStatistic.mean',
                'SectionStatistic.std',
                'SectionStatistic.activity'
            ]);

        if ($query) {
            return $query;
        }
        return null;
    }

    private function getTimeRange($csv_object)
    {
        foreach ($csv_object as $key => $value) {
            $current_date = Carbon::parse($value->timestamp)->format('Y-m-d');
            if (!$key == 0) {
                $previous_time = $csv_object[$key - 1]->timestamp;

                $prev_date = Carbon::parse($previous_time)->format('Y-m-d');
                if ($current_date != $prev_date) {
                    $newTime = $current_date . ' 00:00:00 -   ' . Carbon::parse($value->timestamp)->format('h:i:s A');
                } else {
                    $prev_time = Carbon::parse($previous_time)->format('h:i:s A');

                    $newTime = $current_date . ' ' . $prev_time . ' - ' . Carbon::parse($value->timestamp)->format('h:i:s A');
                }
            } else {
                $newTime = $current_date . ' 00:00:00 - ' . Carbon::parse($value->timestamp)->format('h:i:s A');
            }
            $csv_object[$key]->csv_date = $newTime;
        }

        return $csv_object;
    }

    private function csvColumnsSection()
    {
        $columns = "Barn Id, Section Id, Cycle Id, Date & Time, Average Weight, Total Activity, Standard Deviation";
        return $columns;
    }

    private function csvColumnsBarn()
    {
        $columns = "Barn Id, Cycle Id, Date & Time, Average Weight, Total Activity, Standard Deviation";
        return $columns;
    }

    private function arrayToCsv($columns, $data, $csv_name, $delimiter = ',', $enclosure = '"', $escape_char = "\\")
    {
        Storage::put('public/' . $csv_name, $columns);
        $rows = '';
        $hasSections = Str::contains($columns, 'Section Id');

        foreach ($data as $item) {
            if ($hasSections) {
                $row = [
                    'Barn Id' => $item['barn_id'],
                    'Section Id' => $item['section_id'],
                    'Cycle Id' => $item['cycle_id'],
                    'Date & Time' => $item['csv_date'],
                    'Average Weight' => $item['mean'],
                    'Total Activity' => $item['activity'],
                    'Standard Deviation' => $item['std'],
                ];
            } else {
                $row = [
                    'Barn Id' => $item['barn_id'],
                    'Cycle Id' => $item['cycle_id'],
                    'Date & Time' => $item['csv_date'],
                    'Average Weight' => $item['mean'],
                    'Total Activity' => $item['activity'],
                    'Standard Deviation' => $item['std'],
                ];
            }
            $rows .= implode(',', $row) . PHP_EOL;
        }
        Storage::append('public/' . $csv_name, $rows);
        return Storage::download('public/' . $csv_name);
    }


    private function getTimezone($userId)
    {
        $timezone = Farm::where('manager_user_id', $userId)
            ->value('timezone');
        return $timezone;
    }

    private function covertTimezoneToLocation($time, $userId)
    {
        $timezone = $this->getTimezone($userId);
        $convertDate = Carbon::createFromFormat('H:i:s', $time)
            ->setTimezone($timezone)
            ->format('H:i:s');
        return $convertDate;
    }


    private function changeTimezone($time, $date, $userId)
    {
        $timezone = $this->getTimezone($userId);
        $timestamp = $date . ' ' . $time;
        $convertDate = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp)
            ->setTimezone($timezone)
            ->format('Y-m-d H:i:s');
        return $convertDate;
    }

    public function test()
    {
        $date = '2022-03-25';
        $time = '00:00:00';
        $userId = 4;
        $barnId = 17;
        $cycleId = 29;


        $query = SectionStatistic::join('Section', 'SectionStatistic.section_id', '=', 'Section.id')
            ->where('Section.barn_id', $barnId)
            ->where('SectionStatistic.cycle_id', $cycleId)
            ->orderBy('SectionStatistic.timestamp')
            ->get([
                'SectionStatistic.cycle_id',
                'SectionStatistic.section_id',
                'SectionStatistic.timestamp',
                'SectionStatistic.mean',
                'SectionStatistic.std',
                'SectionStatistic.activity',
                'Section.id',
                'Section.barn_id'
            ]);

        return $query;
    }
}
