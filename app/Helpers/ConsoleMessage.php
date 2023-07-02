<?php

namespace App\Helpers;

/**
 * Gives us the ability to use console info messages outside of a command
 */
class ConsoleMessage extends \Illuminate\Console\Command
{
    protected $name = 'NONEXISTENT';
    protected $hidden = true;

    public $outputSymfony;
    public $outputStyle;

    public function __construct($argInput = "")
    {
        parent::__construct();

        $this->input = new \Symfony\Component\Console\Input\StringInput($argInput);

        $this->outputSymfony = new \Symfony\Component\Console\Output\ConsoleOutput();
        $this->outputStyle = new \Illuminate\Console\OutputStyle($this->input, $this->outputSymfony);

        $this->output = $this->outputStyle;
    }

}
