<?php

/*
 * This file is part of the Nielsiano\DmplBuilder package.
 *
 * (c) Niels Stampe <nielsstampe@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nielsiano\DmplBuilder;

/**
 * Class DmplBuilder
 *
 * @package Nielsiano\DmplBuilder
 */
class DmplBuilder implements PlotBuilder
{

    /**
     * Generated DM/PL command instructions.
     */
    protected array $instructions = [];

    protected bool $cutOff = false;
    protected bool $flipAxes = false;
    protected string $measuringUnit = 'M';

    const KISS_CUT = 50;
    const FLEXCUT_PEN = 6;
    const REGULAR_PEN = 0;
    const CUT_THROUGH = 100;
    const MEASURING_UNITS = [1, 2, 3, 4, 5, 'M'];

    /**
     * Adds a new plot of x and y to machine instructions.
     */
    public function plot(int $x, int $y): PlotBuilder
    {
        array_map([$this, 'pushCommand'], $this->flipAxes ? [$y, $x] : [$x, $y]);

        return $this;
    }

    /**
     * Changes the pen of the plotter.
     */
    public function changePen(int $pen): PlotBuilder
    {
        if (! in_array($pen, range(0, 6))) {
            throw new \InvalidArgumentException(sprintf('[%d] is not a valid Pen.', $pen));
        }

        return $this->pushCommand(sprintf('P%d;', $pen));
    }

    /**
     * Compiles a string in DMPL format with machine instructions.
     */
    public function compile(): string
    {
        $init = sprintf(';: EC%s,U H L0,A100,100,R,', $this->measuringUnit);

        $this->pushCommand($this->cutOff ? ';:c,e' : 'e');

        return $init . implode(',', $this->instructions);
    }

    /**
     * Alias for `compile()` (for backwards compatibility).
     */
    public function compileDmpl(): string
    {
        return $this->compile();
    }

    /**
     * Pushes a command to the instructions.
     */
    public function pushCommand(string $command): PlotBuilder
    {
        $this->instructions[] = $command;

        return $this;
    }

    /**
     * Lifts the pen up.
     */
    public function penUp(): PlotBuilder
    {
        return $this->pushCommand('U');
    }

    /**
     * Pushes the pen down on paper.
     */
    public function penDown(): PlotBuilder
    {
        return $this->pushCommand('D');
    }

    /**
     * Changes the plotter pen to use flexcut.
     */
    public function flexCut(): PlotBuilder
    {
        return $this->changePen(self::FLEXCUT_PEN);
    }

    /**
     * Change to the regular plotter pen.
     */
    public function regularCut(): PlotBuilder
    {
        return $this->changePen(self::REGULAR_PEN);
    }

    /**
     * Changes the pen pressure in gram.
     */
    public function pressure(int $gramPressure): PlotBuilder
    {
        return $this->pushCommand(sprintf('BP%d;', $gramPressure));
    }

    /**
     * Specifies measuring unit.
     * 1 selects 0.001 inch
     * 5 selects 0.005 inch
     * M selects 0.1 mm
     */
    public function setMeasuringUnit($unit): PlotBuilder
    {
        if (! in_array($unit, self::MEASURING_UNITS)) {
            throw new \InvalidArgumentException(sprintf('[%s] is not a valid measuring unit.', $unit));
        }

        $this->measuringUnit = $unit;

        return $this;
    }

    /**
     * Changes the plotter velocity.
     */
    public function velocity(int $velocity): PlotBuilder
    {
        return $this->pushCommand(sprintf('V%d;', $velocity));
    }

    /**
     * Flips the x, y coordinates.
     */
    public function flipAxes(): PlotBuilder
    {
        $this->flipAxes = true;

        return $this;
    }

    /**
     * Cuts off paper when a operation finishes.
     */
    public function cutOff(): PlotBuilder
    {
        $this->cutOff = true;

        return $this;
    }

}
