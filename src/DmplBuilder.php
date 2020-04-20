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

    const REGULAR_PEN = 0;
    const KISS_CUT_PEN = 1;
    const THROUGH_CUT_PEN = 5;
    const FLEX_CUT_PEN = 6;
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
     * Adds a circle with radius r centered in (x,y)
     */
    public function circle(int $x, int $y, int $r): PlotBuilder
    {
        if ($this->flipAxes) {
            [$x, $y] = [$y, $x];
        }
        $this->pushCommand("CC {$x}");
        $this->pushCommand($y);
        $this->pushCommand($r);

        return $this;
    }

    /**
     * Adds a circle arc.
     * (x,y) specified the center of the circle which contains the arc.
     * d specifies the size of the arc in degrees between -360 to +360.
     * + causes counterclockwise movement, and - causes clockwise movement.
     */
    public function arc(int $x, int $y, int $d): PlotBuilder
    {
        if ($this->flipAxes) {
            [$x, $y] = [$y, $x];
        }
        $this->pushCommand("CA {$x}");
        $this->pushCommand($y);
        $this->pushCommand($d);

        return $this;
    }

    /**
     * Adds an ellipse with center (x,y), lateral axis length (x1,y1) and vertical axis height (x2,y2)
     */
    public function ellipse(int $x, int $y, int $x1, int $y1, int $x2, int $y2): PlotBuilder
    {
        if ($this->flipAxes) {
            [$x, $y] = [$y, $x];
            [$x1, $y1] = [$y1, $x1];
            [$x2, $y2] = [$y2, $x2];
        }
        $this->pushCommand("CE {$x}");
        $this->pushCommand($y);
        $this->pushCommand($x1);
        $this->pushCommand($y1);
        $this->pushCommand($x2);
        $this->pushCommand($y2);

        return $this;
    }

    /**
     * Adds a curve of points to be connected.
     * (xn, yn) determines the slope of the curved line at the last plot point.
     */
    public function curve(int $x, int $y, int $x1, int $y1, ...$points): PlotBuilder
    {
        if (count($points) % 2) {
            throw new \InvalidArgumentException('Odd number of arguments');
        }

        if ($this->flipAxes) {
            [$x, $y] = [$y, $x];
        }

        $this->pushCommand("CG {$x}");
        $this->pushCommand($y);

        foreach (array_chunk([$x1, $y1, ...$points], 2) as [$xn, $yn]) {
            if ($this->flipAxes) {
                [$xn, $yn] = [$yn, $xn];
            }
            $this->pushCommand($xn);
            $this->pushCommand($yn);
        }

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
     * Changes the plotter pen to use flex cut (perforated).
     */
    public function flexCut(): PlotBuilder
    {
        return $this->changePen(self::FLEX_CUT_PEN);
    }

    /**
     * Change to the regular plotter pen.
     */
    public function regularCut(): PlotBuilder
    {
        return $this->changePen(self::REGULAR_PEN);
    }

    public function throughCut(): PlotBuilder
    {
        return $this->changePen(self::THROUGH_CUT_PEN);
    }

    public function kissCut(): PlotBuilder
    {
        return $this->changePen(self::KISS_CUT_PEN);
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
