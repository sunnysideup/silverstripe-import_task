<?php


/**
 * allows the creation of records from a CSV
 * data should be formatted in CSV like this:
 *
 * NAME (name of )
 * COUNTRY - New Zealand
 * COUNTRYCODE - e.g. NZ, AU, US
 * TYPE (retailler / agent)
 * CITY - e.g. Amsterdam
 * WEB - e.g. http://www.mysite.co.nz
 * EMAIL
 * PHONE e.g. +31 33323321
 * ADDRESS
 * 
 * you can use ANY fields you like... this is just an example
 */

abstract class ImportTaskBasics extends BuildTask {

    protected $title = "Extend this class";

    protected $description = "
        Extend this class ...
    ";

    /**
     * excluding base folder
     *
     * e.g. assets/files/mycsv.csv
     * @var String
     */
    protected $fileLocation;

    /**
     * excluding base folder
     *
     * e.g. assets/files/mycsv.csv
     * @var String
     */
    protected $csvSeparator = ",";


    /**
     * @var Boolean
     */
    protected $enabled = false;


    /**
     * @var Boolean
     */
    protected $debug = true;

    /**
     * @var array
     */
    protected static $characters_to_replace = array(
        '’' => '\''
    );


    /**
     * the original data from the CVS
     * @var Array
     */
    protected $csv = array();

    function getDescription(){
        return $this->description .'<br /> The file to be used is: <strong>'.$this->fileLocation.'</strong>';
    }

    public function RunFromCode($reset, $run) {
        if($reset) {
            $_GET["reset"] = 1;
        }
        if($run) {
            $_GET["run"] = 1;
        }
        $this->run(null);
    }

    /**
     *
     */
    public function run($request){

        increase_time_limit_to(3600);
        set_time_limit(3600);
        increase_memory_limit_to('1024M');
        if(isset($_GET["resetonly"])) {
            //do nothing
        }
        else {
            $this->readFile();
        }

        if(isset($_GET["reset"]) && $_GET["reset"] == 1) {
            $this->deleteRecords();
        }

        if(isset($_GET["run"]) && $_GET["run"] == 1) {
            $this->createRecords();
        }
        $resetLink =  $this->Link(null, false, true);
        $runLink =  $this->Link(null, true, false);
        $allLink =  $this->Link(null, true, true);
        $this->outputToScreen("================================================");
        $this->outputToScreen($resetLink);
        $this->outputToScreen($runLink);
        $this->outputToScreen($allLink);
        $this->outputToScreen("================================================");
        $this->outputToScreen("================ THE END =======================");
        $this->outputToScreen("================================================");
    }

    protected function readFile(){
        $this->outputToScreen("
            ================================================
            READING FILE ".ini_get('max_execution_time')."seconds available. ".(ini_get('memory_limit'))."MB available
            ================================================");
        if(!$this->fileLocation) {
            $this->outputToScreen("There is no file to import", "deleted");
            return;
        }
        $rowCount = 1;
        $rows = array();
        $fileLocation = Director::baseFolder()."/".$this->fileLocation;
        $this->outputToScreen("reading file $fileLocation", "deleted");
        $replaceFromChars = array_keys($this->Config()->get('characters_to_replace'));
        $replaceToChars = array_values($this->Config()->get('characters_to_replace'));

        if (($handle = fopen($fileLocation, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 100000, $this->csvSeparator)) !== FALSE) {
                $cleanArray = array();
                foreach($data as $key => $value) {

                    $value = str_replace($replaceFromChars, $replaceToChars, $value);
                    $cleanArray[trim($key)] = trim($value);

                }
                $rows[] = $cleanArray;
                $rowCount++;
            }
            fclose($handle);
        }
        //$rows = str_getcsv(file_get_contents(, ",", '"');

        $header = array_shift($rows);

        $this->csv = array();
        $rowCount = 1;
        foreach ($rows as $row) {
            if(count($header) != count($row)) {
                $this->outputToScreen("I am trying to merge ".implode(", ", $header)." with ".implode(", ", $row)." but the column count does not match!", "deleted");
                die("STOPPED");
            }
            $this->csv[] = array_combine($header, $row);
            $rowCount++;
        }
        $this->outputToScreen("Imported ".count($this->csv)." rows with ".count($header)." cells each");
        $this->outputToScreen("Fields are: ".implode(", ", $header));
        $this->outputToScreen("================================================");

    }


    /**
     * created the records
     *
     */
    abstract protected function createRecords();

    /**
     * delete the records
     *
     */
    abstract protected function deleteRecords();

    /**
     *
     *
     * @param  string $message
     * @param  string $type
     */
    protected function outputToScreen($message, $type = "")
    {
            echo " ";
            flush(); ob_end_flush(); DB::alteration_message($message, $type); ob_start();
    }

    public function Link($action = null, $run = false, $reset = false) {
        $link =
            "/dev/tasks/".
            $this->class.'/'.
            ($action ?$action.'/' : '').
            "?run=".($run ? 1 : 0).
            "&reset=".($reset ? 1 : 0);
        return
            '<h3>'.$this->getTitle().': <a href="'.$link.'">'.
            ($reset ? 'reset' : '').' '.($reset && $run ? 'AND' : '').' '.($run ? 'run' : '').($action ? ' - '.$action : '').
            '</a></h3>';
    }

}
