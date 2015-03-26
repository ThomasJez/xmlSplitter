<?php

namespace XmlSplitter;

use XMLReader;
use Symfony\Component\Filesystem\Filesystem;

class XmlSplitter {

    /**
     * @var XMLReader;
     */
    protected $reader;

    /**
     * @var array
     */
    protected $files = array();

    /**
     * @var string
     */
    protected $outputFolder;

    /**
     * @var string
     */
    protected $nameByTag;

    /**
     * @var string
     */
    protected $nameByAttribute;

    /**
     * @var int
     */
    protected $fileCount = 1;

    /**
     * @var array
     */
    protected $tagFilter = array();

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param XMLReader $reader
     * @param string    $input
     */
    public function __construct(XMLReader $reader, $input)
    {
        $this->reader = $reader;
        $this->setFilesystem(new Filesystem());

        if (is_file($input)) {
            $this->addFile($input);
        } else if (is_dir($input)) {
            $this->setFiles(glob(sprintf('%s/*.xml', $input)));
        }

    }

    /**
     * @param string $tag
     * @throws \Exception
     */
    public function split($tag)
    {
        foreach ($this->getFiles() as $file) {

            if (!file_exists($file)) {
                throw new \Exception('The given XML File does not exist');
            }

            $this->reader->open($file);
            while ($this->rFind($tag)) {
                if ($this->hasTagFilter()) {
                    $xml = $this->createSimpleXmlElement();
                    echo $xml->asXML();
                    // todo refactor
                    $filterTag = key($this->getTagFilter());
                    $filterValue = $this->getTagFilter($filterTag);
                    var_dump(sprintf('//%s[text()="%s"]', $filterTag, $filterValue));
                    $result = $xml->xpath(sprintf('//%s[text()="%s"]', $filterTag, $filterValue));
                    var_dump($result);
                    if (empty($result)) {
                        continue;
                    }
                }

                $outputFolder = $this->checkAndCreateOutputFolder($file);
                $this->writeOutput($outputFolder);
            }
        }

    }

    /**
     * write the output
     */
    protected function writeOutput($outputFolder)
    {
        $xml = $this->createSimpleXmlElement();

        $filename = $outputFolder . '/' . $this->getOutputFileName($xml) . '.xml';
        file_put_contents($filename, $xml->saveXML());
    }

    /**
     * create a SimpleXMLElement with the value of the current position of the XMLReader
     *
     * @return \SimpleXMLElement
     */
    protected function createSimpleXmlElement()
    {
        $dom = new \DomDocument();
        $n = $dom->importNode($this->reader->expand(), true);
        $dom->appendChild($n);

        return simplexml_import_dom($n);
    }

    /**
     * return the output file name
     *
     * @param \SimpleXMLElement $xml
     * @return string
     */
    protected function getOutputFileName(\SimpleXMLElement $xml)
    {
        $filename = (string) $this->getFileCount();
        if (!is_null($this->getNameByTag()) && is_null($this->getNameByAttribute())) {
            $tag = $this->getNameByTag();
            var_dump($tag);echo $xml->asXML();
            $filename = (string) $xml->$tag;
        }
        else if (is_null($this->getNameByTag()) && !is_null($this->getNameByAttribute())) {
            $attribute = $this->getNameByAttribute();
            $filename = (string) $xml->attributes()->$attribute;
        }
        else if (!is_null($this->getNameByTag()) && !is_null($this->getNameByAttribute())) {

            $tag = $this->getNameByTag();
            $attribute = $this->getNameByAttribute();
            $filename = (string) $xml->$tag->attributes()->$attribute;
        }

        $this->increaseFileCount();

        var_dump($filename);
        return $filename;
    }

    /**
     * check if the OutputFolder exists and if not it will create it
     *
     * @var string $filename
     * @return string
     */
    protected function checkAndCreateOutputFolder($filename)
    {
        $filename =  basename($filename, '.xml');
        $outputFolder = $this->hasOutputFolder() ? $this->getOutputFolder() : $this->getDefaultOutputFolder();
        $outputFolder .= '/' . $filename;

        if (!$this->getFilesystem()->exists($outputFolder)) {
            $this->getFilesystem()->mkdir($outputFolder, 0755);
        }

        return $outputFolder;
    }

    /**
     * find next start tag with a certain name (performance optimized)
     *
     * @param string $tag
     * @return boolean
     */
    protected function rFind($tag) {
        $read_success = null;
        while (
            ($read_success = $this->reader->read()) &&
            !(XMLReader::ELEMENT === $this->reader->nodeType && $tag === $this->reader->name)
            && !(XMLReader::NONE === $this->reader->nodeType)
        ) {
            continue;
        };

        return $read_success && $tag === $this->reader->name;
    }

    /**
     * @param array $files
     *
     * @return XmlSplitter
     */
    public function setFiles(Array $files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @return string
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function addFile($file)
    {
        $this->files[] = $file;
    }

    /**
     * @param string $outputFolder
     *
     * @return XmlSplitter
     */
    public function setOutputFolder($outputFolder)
    {
        $this->outputFolder = $outputFolder;

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputFolder()
    {
        return $this->outputFolder;
    }

    public function hasOutputFolder()
    {
        return !is_null($this->outputFolder);
    }

    /**
     * @param \XMLReader $reader
     *
     * @return XmlSplitter
     */
    public function setReader($reader)
    {
        $this->reader = $reader;

        return $this;
    }

    /**
     * @return \XMLReader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * @param string $nameByAttribute
     *
     * @return XmlSplitter
     */
    public function setNameByAttribute($nameByAttribute)
    {
        $this->nameByAttribute = $nameByAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getNameByAttribute()
    {
        return $this->nameByAttribute;
    }

    /**
     * @param string $nameByTag
     *
     * @return XmlSplitter
     */
    public function setNameByTag($nameByTag)
    {
        $this->nameByTag = $nameByTag;

        return $this;
    }

    /**
     * @return string
     */
    public function getNameByTag()
    {
        return $this->nameByTag;
    }

    /**
     * @param int $fileCount
     *
     * @return XmlSplitter
     */
    public function increaseFileCount($add = 1)
    {
        $this->fileCount = $this->fileCount + $add;

        return $this;
    }

    /**
     * @return int
     */
    public function getFileCount()
    {
        return $this->fileCount;
    }

    protected function getDefaultOutputFolder()
    {
        return $this->outputFolder = sys_get_temp_dir() . '/xml_splitter_output/';
    }

    /**
     * @return array|string
     */
    public function getTagFilter($tag = null)
    {
        if (!is_null($tag) && isset($this->tagFilter[$tag])) {
            return $this->tagFilter[$tag];
        }

        return $this->tagFilter;
    }

    /**
     * @param array $tagFilter
     *
     * @return XmlSplitter
     */
    public function setTagFilter(Array $tagFilter)
    {
        $this->tagFilter = $tagFilter;

        return $this;
    }

    public function addTagFilter($tag, $filter)
    {
        $this->tagFilter[$tag] = $filter;
    }

    public function hasTagFilter()
    {
        return !empty($this->tagFilter);
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @param Filesystem $filesystem
     *
     * @return XmlSplitter
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        return $this;
    }
}
