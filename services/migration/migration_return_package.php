<?php
class migration_return_package
{
    public $data;
    public $returnCode;
    public $returnCodeDescription;

    public function migration_return_package($returnCode, $data = NULL, $returnCodeDescription=NULL)
    {
        $this->data = $data;
        $this->returnCode = $returnCode;
        $this->returnCodeDescription = $returnCodeDescription;
        return $this;
    }
}
?>
