<?php

namespace Jimmyjs\ReportGenerator\ReportMedia;

use League\Csv\Writer;
use App, Closure, Exception;
use Jimmyjs\ReportGenerator\ReportGenerator;

class CSVReport extends ReportGenerator
{
    protected $showMeta = false;

    public function download($filename, $save = false)
    {
        if (!class_exists(Writer::class)) {
            throw new Exception(__('laravel-report-generator::exceptions.league_csv_not_found'));
        }

        if ($save) {
            $filePath = $filename;
            $csv = Writer::createFromPath($filePath, 'w');
        } else {
            $csv = Writer::createFromFileObject(new \SplTempFileObject());
        }

        if ($this->showMeta) {
            foreach ($this->headers['meta'] as $key => $value) {
                $csv->insertOne([$key, $value]);
            }
            $csv->insertOne([' ']);
        }

        $ctr = 1;

        if ($this->showHeader) {
            $columns = array_keys($this->columns);
            if (!$this->withoutManipulation && $this->showNumColumn) {
                array_unshift($columns, 'No');
            }
            $csv->insertOne($columns);
        }

        foreach($this->query->take($this->limit ?: null)->cursor() as $result) {
            if ($this->withoutManipulation) {
                $data = $result->toArray();
                if (count($data) > count($this->columns)) array_pop($data);
                $csv->insertOne($data);
            } else {
                $formattedRows = $this->formatRow($result);
                if ($this->showNumColumn) array_unshift($formattedRows, $ctr);
                $csv->insertOne($formattedRows);
            }
            $ctr++;
        }

        if (!$save) {
            $csv->output($filename . '.csv');
        }
    }

    public function store($filename)
    {
        $this->download($filename, true);
    }

    private function formatRow($result)
    {
        $rows = [];
        foreach ($this->columns as $colName => $colData) {
            if (is_object($colData) && $colData instanceof Closure) {
                $generatedColData = $colData($result);
            } else {
                $generatedColData = $result->$colData;
            }
            $displayedColValue = $generatedColData;
            if (array_key_exists($colName, $this->editColumns)) {
                if (isset($this->editColumns[$colName]['displayAs'])) {
                    $displayAs = $this->editColumns[$colName]['displayAs'];
                    if (is_object($displayAs) && $displayAs instanceof Closure) {
                        $displayedColValue = $displayAs($result);
                    } elseif (!(is_object($displayAs) && $displayAs instanceof Closure)) {
                        $displayedColValue = $displayAs;
                    }
                }
            }

            array_push($rows, $displayedColValue);
        }

        return $rows;
    }
}
