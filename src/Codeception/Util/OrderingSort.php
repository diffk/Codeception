<?php
namespace Codeception\Util;

/**
 * Class orderingSort
 */
class OrderingSort
{

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var string
     */
    private $mask = '';

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var array
     */
    private $orderListPath = [];

    /**
     * @var array
     */
    private $referenceSortList = [];

    /**
     * @var array
     */
    private $toSort = [];

    /**
     * @var array
     */
    private $bufferList = [];

    /**
     * @var array
     */
    private $resultList = [];

    /**
     * @var array
     */
    private $sourceList = [];

	/**
	 * @var string
	 */
	private $ignorePrefix = '00';

    /**
     * getGroupFromNumber
     *
     * @param mixed $group
     * @access public
     * @return void
     */
    private function getGroupFromNumber($group)
    {
        $set = explode(".", $group);

        return $set[0];
    }

    /**
     * getSubGroupFromNumber
     *
     * @param mixed $group
     * @access public
     * @return void
     */
    private function getSubGroupFromNumber($group)
    {
        $set = explode(".", $group);

        return $set[1];
    }

    /**
     * getNomerFromNumber
     *
     * @deprecated
     * @param mixed $group
     * @access public
     * @return void
     */
    private function getNomerFromNumber($group)
    {
        $set = explode(".", $group);

        return $set[2];
    }

    /**
     * @param string $targetPath
     * @param string $mask
     * @param string $orderListPath
     * @param bool|false $debug
     */
    public function __construct($targetPath, $mask = "*.php", $orderListPath, $debug = false)
    {
        $this->path = $targetPath;
        $this->mask = $mask;
        $this->orderListPath = $orderListPath;
        $this->debug = $debug;
        $this->init();
    }


    /**
     * readOrderedList
     *
     * @access private
     * @return void
     */
    private function readOrderedList()
    {
        //-- load reference
        $raws = file($this->orderListPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $buffer = [];

        // an attempt to filter out invalid values
        foreach ($raws as $raw) {
            if (!strpos($raw, '-')) {
                $buffer[] = $raw;
            } else {
                // ??$buffer[] = $r;
            }
        }

        $this->referenceSortList = $buffer;

    }


    /**
     * init State
     *
     * @access private
     * @return void
     */
    private function init()
    {

        $this->readOrderedList();

        // подготовимся, считаем значения и сохраним в паре массивов
        foreach (glob($this->path . '/' . $this->mask) as $filename) {

            $fileNameShort = basename($filename);

            if (stripos($fileNameShort, '-') === false) {
                continue;
            }

			if (substr($fileNameShort, 0, 2) === $this->ignorePrefix) {
				continue;
			}

            $index = mb_strstr($fileNameShort, "-");
            $index = str_replace($index, "", $fileNameShort);

            $this->toSort[] = $index;
            $this->sourceList[$index] = $filename;

            if (in_array($index, $this->referenceSortList)) {
                $this->resultList[] = $index;
            } else {
                $this->bufferList[] = $index;
            }
        }


        $this->_print("reference: ", $this->referenceSortList);
        $this->_print("buffer: ", $this->bufferList);
        $this->_print("result not filtered :", $this->resultList);


        $remap = [];

        foreach ($this->referenceSortList as $el) {
            if (in_array($el, $this->resultList)) {
                $remap[] = $el;
            }
        }

        $this->resultList = $remap;

        $this->_print("result filtered :", $this->resultList);

    }

    /**
     * Process sorting
     *
     * @return array
     */
    public function sortResultList()
    {

        foreach ($this->bufferList as $buffer) {
            $this->pushElementToSort($buffer);
        }

        return $this->resultList;

    }

    /**
     * @param $index
     */
    private function pushElementToSort($index)
    {
        $buffer = [];

        $group = $this->getGroupFromNumber($index);
        $subGroup = $this->getSubGroupFromNumber($index);

        $flag = false;
        $saveGroupPosition = 0;
        $saveSubGroupPosition = 0;

        /**
         * Find the last known position
         */
        foreach ($this->resultList as $key => $val) {
            if ($this->getGroupFromNumber($val) === $group) {
                $saveGroupPosition = $key;
                if ($this->getSubGroupFromNumber($val) === $subGroup) {
                    $saveSubGroupPosition = $key;
                }
            }
        }

        /**
         * insert before the last element
         */
        foreach ($this->resultList as $key => $val) {
            $buffer[] = $val;

            if ($saveSubGroupPosition && $saveSubGroupPosition === $key && !$flag) {

                if (count($buffer) > 0) {
                    $endElGroup = array_pop($buffer);
                    $buffer[] = $index;
                    $buffer[] = $endElGroup;
                }

                $flag = true;

            } elseif ($saveGroupPosition && $saveGroupPosition === $key && !$flag) {

                if (count($buffer) > 0) {
                    $endElGroup = array_pop($buffer);
                    $buffer[] = $index;
                    $buffer[] = $endElGroup;
                }

                $flag = true;
            }
        }

        //-- empty buffer?
        if (!$flag) {
            $buffer[] = $index;
        }

        $this->resultList = $buffer;

    }

    /**
     * _print
     */
    public function _print()
    {
        if(!$this->debug) {
            return;
        }

        $messages = func_get_args();

        foreach ($messages as $message) {
            if (is_array($message)) {
                foreach ($message as $line) {
                    echo $line . "\n\r";
                }
            } else {
                echo $message . "\n\r";
            }
        }

    }

}
