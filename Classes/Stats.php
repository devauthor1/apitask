<?php

/**
 * Class Stats
 */
class Stats {

    /**
     * Class constants
     */
    const SOURCE_FILE = 'file';
    const SOURCE_DB = 'db';
    const SOURCE_API = 'api';

    const MONTH = 'month';
    const YEAR = 'year';

    const YEAR_BEGIN = 2000;
    const YEAR_END = 2025;

    /**
     * @var $setup
     */
    private $setup;

    /**
     * Class constructor
     */
    public function __construct($setup) 
    {
        $this->setup = json_decode(json_encode((object) $setup), FALSE);
    }
  
    /**
     * Returns total number of visits as array
     */
    public function getTotalNumberOfVisits(): array
    {
        $sources = $this->setup;
        $request = $_REQUEST;
        $params = $this->prepareParamsAsArray($request);

        $visits = [];
        foreach ($sources as $key => $source) {
            if ($key == self::SOURCE_FILE)
                $visits[] = $this->getVisitsFromFile($source->directories, $source->fileExtension, $source->separator, $params);

            if ($key == self::SOURCE_DB)
                $visits[] = $this->getVisitsFromDb($source->dbNames, $params);
                
            if ($key == self::SOURCE_API)
                $visits[] = $this->getVisitsFromApi($source->apiNames, $params);    
        }

        $totalVisits = [];
        array_walk_recursive($visits, function($value, $key) use (&$totalVisits) { 
            if (isset($totalVisits[$key])) {
                $totalVisits[$key] += $value;
            } else {
                $totalVisits[$key] = $value;
            }
             
        });
        
        return $totalVisits;
    }

    /**
     * Returns parameters month and year for filtering visits data
     */
    private function prepareParamsAsArray($request): array
    {
        $params = [];
        if ($request) {
            foreach ($request as $key => $param) {
                if (strtolower($key) == self::MONTH) {
                    $param > 0 && $param <= 12 ? $params[strtolower($key)] = $param : '';
                }                 

                if (strtolower($key) == self::YEAR) {
                    $param >= self::YEAR_BEGIN && $param <= self::YEAR_END ? $params[strtolower($key)] = (int) $param : '';
                }

                !isset($params[self::MONTH]) ? $params[self::MONTH] = date('m') : '';
                !isset($params[self::YEAR]) ? $params[self::YEAR] = (int) date('Y') : '';
            }
        }

        return $params;
    }

    /**
     * Returns visits counted from files 
     */
    private function getVisitsFromFile($directories, $fileExtension, $separator, $params = []): array
    {
        $visits = [];
        foreach ($directories as $directory) {
            $dir = is_dir(__DIR__ . '/' . $directory);
            
            if ($dir) {
                $files = scandir(__DIR__ . '/' . $directory);
                foreach ($files as $file) {
                    $fileExt = pathinfo($file, PATHINFO_EXTENSION);

                    if ($fileExt == $fileExtension) {
                        $dataFromFile = file(__DIR__ . '/' . $directory . '/' . $file);
                        foreach ($dataFromFile as $fileRow) {
                            list($date, $site, $nrVisits) = explode($separator, $fileRow);
                        
                            if ($params) {
                                list($year, $month) = explode('-', $date);
                    
                                if ((int) $year === $params['year'] && $month === $params['month']) {
                                    if (isset($visits[$site])) {
                                        $visits[$site] += (int) $nrVisits;
                                    } else {
                                        $visits[$site] = (int) $nrVisits;
                                    }
                                }                       
                            } else {
                                if (isset($visits[$site])) {
                                    $visits[$site] += (int) $nrVisits;
                                } else {
                                    $visits[$site] = (int) $nrVisits;
                                }
                            }                         
                        } 
                    }                 
                }
            }            
        }
        
        return $visits;
    }

    /**
     * Returns visits counted from DBs
     */
    private function getVisitsFromDb($dbNames, $params = []): array
    {
        $visits = [];
        foreach($dbNames as $db) {
            $dsn = "mysql:host=" . $db->dbhost . ";dbname=" . $db->dbname;
            $user = $db->dbuser;
            $password = $db->dbpassword;

            try {
                $pdo = new PDO($dsn, $user, $password);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }           

            if ($params) {
                $stmt = 
                    $pdo->prepare("SELECT site, SUM(visits) visits FROM visits WHERE monthNr = :month AND yearNr = :year GROUP BY site");
                $stmt->bindParam(':month', $params['month'], PDO::PARAM_INT);
                $stmt->bindParam(':year', $params['year'], PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare('SELECT site, SUM(visits) visits FROM visits GROUP BY site');
            }

            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                if (isset($visits[$row['site']])) {
                    $visits[$row['site']] += $row['visits'];
                } else {
                    $visits[$row['site']] = $row['visits'];
                }
            }
        }

        return $visits;
    }

    /**
     * Returns visits counted from APIs 
     */
    private function getVisitsFromApi($apiNames, $params = []): array
    {
        $visits = [];
        foreach ($apiNames as $api) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api->url);

                if ($params) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, false);
                }
                
                curl_setopt($ch, CURLOPT_POST, 1);                
                $results = curl_exec($ch);
                curl_close($ch);

                if ($results) {
                    foreach ($results as $result) {
                        $result = json_decode($result);
                        
                        if (isset($visits[$result['data']['site']])) {
                            $visits[$result['data']['site']] += $result['data']['visits'];
                        } else {
                            $visits[$result['data']['site']] = $result['data']['visits'];
                        }
                    }
                }
            } catch (\Exception $e) {
                throw new Exception($e->getMessage());
            }
        }

        return $visits; 
    }

}
