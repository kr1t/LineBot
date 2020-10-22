<?php
namespace Krit;

use Illuminate\Http\Request;

class LineWebHookEvent
{
    public $text;

    public function __construct()
    {
        $this->setRequest();
    }

    public function __call($name, $arg)
    {
        call_user_func($name, $arg);
    }

    public function setRequest()
    {
        $request = new Request();
        $this->text = $request->all();
        return $this;
    }
}
