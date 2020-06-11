<?php

namespace Nielsiano\DmplBuilder;

use Exception;

/**
 * Illustrate a plot program visually as an SVG line drawing.
 */
class SvgBuilder implements PlotBuilder
{
    /**
     * Current position.
     */
    protected $x = 0;
    protected $y = 0;

    /**
     * Extent of drawing
     */
    protected $maxX = 0;
    protected $maxY = 0;

    protected $instructions = [];

    protected $axesFlipped = false;
    protected $penIsDown = true;
    protected $tool = 'regular';
    protected $unit = 'mm';
    protected $scale = 0.1;

    const TOOLS = [
        0 => 'regular',
        1 => 'kiss',
        5 => 'through',
        6 => 'flex',
    ];

    /**
     * Adds a new plot of x and y to machine instructions.
     */
    public function plot(int $x, int $y): PlotBuilder
    {

        if ($this->axesFlipped) {
            [$x, $y] = [$y, $x];
        }

        $targetX = $this->x + $x;
        $targetY = $this->y + $y;

        $this->maxX = max($this->maxX, $targetX);
        $this->maxY = max($this->maxY, $targetY);

        if ($this->penIsDown) {
            $this->pushInstruction('line', [
                'x1'    => $this->x,
                'y1'    => $this->y,
                'x2'    => $targetX,
                'y2'    => $targetY,
                'class' => $this->tool
            ]);
        }

        $this->x = $targetX;
        $this->y = $targetY;

        return $this;
    }

    /**
     * Changes the pen of the plotter.
     */
    public function changePen(int $pen): PlotBuilder
    {
        $this->tool = self::TOOLS[$pen];

        return $this;
    }

    /**
     * Compiles a string in target format with machine instructions.
     */
    public function compile(): string
    {
        $instructions = implode("\n", $this->instructions);

        $width = ($this->maxX * $this->scale) . $this->unit;
        $height = ($this->maxY * $this->scale) . $this->unit;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$this->maxX} {$this->maxY}">
    <defs>
        <style>
            .regular {
                stroke: rgb(0,0,255);
                stroke-width: 4;
            }

            .kiss {
                stroke: rgb(0,0,255);
                stroke-width: 4;
                stroke-dasharray: 20 4;                
            }

            .flex {
                stroke: rgb(255,0,0);
                stroke-width: 4;
                stroke-dasharray: 20 4;
            }
            
            .through {
                stroke: rgb(255,0,0);
                stroke-width: 4;
            }
            
            path {
                fill: none;
            }
        </style>
    </defs>
    {$instructions}
</svg>
SVG;
    }

    /**
     * Pushes a command to the instructions.
     *
     * No effect in the SVG output.
     */
    public function pushCommand(string $command): PlotBuilder
    {
        return $this;
    }

    /**
     * Lifts the pen up.
     */
    public function penUp(): PlotBuilder
    {
        $this->penIsDown = false;

        return $this;
    }

    /**
     * Pushes the pen down on paper.
     */
    public function penDown(): PlotBuilder
    {
        $this->penIsDown = true;

        return $this;
    }

    /**
     * Changes the plotter pen to use flexcut.
     */
    public function flexCut(): PlotBuilder
    {
        return $this->changePen(6);
    }

    public function throughCut(): PlotBuilder
    {
        return $this->changePen(5);
    }

    public function kissCut(): PlotBuilder
    {
        return $this->changePen(1);
    }

    /**
     * Change to the regular plotter pen.
     */
    public function regularCut(): PlotBuilder
    {
        return $this->changePen(0);
    }

    /**
     * Changes the pen pressure in gram.
     *
     * No effect in the SVG output.
     */
    public function pressure(int $gramPressure): PlotBuilder
    {
        return $this;
    }

    /**
     * Specifies measuring unit.
     * 1 selects 0.001 inch
     * 5 selects 0.005 inch
     * M selects 0.1 mm
     *
     * @throws Exception
     */
    public function setMeasuringUnit($unit): PlotBuilder
    {
        switch ($unit) {
            case 'M':
                $this->unit = 'mm';
                $this->scale = 0.1;
                break;
            case 1:
                $this->unit = 'in';
                $this->scale = 0.001;
                break;
            case 5:
                $this->unit = 'in';
                $this->scale = 0.005;
                break;
            default:
                throw new Exception('Unhandled unit: ' . $unit);
        }

        return $this;
    }

    /**
     * Changes the plotter velocity.
     *
     * No effect in the SVG output.
     */
    public function velocity(int $velocity): PlotBuilder
    {
        return $this;
    }

    /**
     * Flips the x, y coordinates.
     */
    public function flipAxes(): PlotBuilder
    {
        $this->axesFlipped = true;

        return $this;
    }

    /**
     * Cuts off paper when a operation finishes.
     *
     * No effect in the SVG output.
     */
    public function cutOff(): PlotBuilder
    {
        return $this;
    }

    protected function pushInstruction(string $name, array $parameters): PlotBuilder
    {
        $instruction = '<' . $name;
        foreach ($parameters as $parameter => $value) {
            $instruction .= ' ' . $parameter . '="' . htmlspecialchars($value) . '"';
        }
        $this->instructions[] = $instruction . ' />';

        return $this;
    }

    public function circle(int $x, int $y, int $r): PlotBuilder
    {
        // TODO: Implement circle() method.
    }

    /**
     * Adds a circle arc.
     * (x,y) specified the center of the circle (relative to current position) which contains the arc.
     * d specifies the size of the arc in degrees between -360 to +360.
     * + causes counterclockwise movement, and - causes clockwise movement.
     */
    public function arc(int $dx, int $dy, int $degrees): PlotBuilder
    {
        $radius = sqrt($dx ** 2 + $dy ** 2);

        // FIXME: Not correctly implemented, but gives visually correct results for angles > 180°
        $degrees += 180;

        $endX = $dx + ($radius * cos($degrees * M_PI / 180));
        $endY = $dy + ($radius * sin($degrees * M_PI / 180));

        $this->maxX = max($this->maxX, $this->x + $dx + $radius);
        $this->maxY = max($this->maxY, $this->y + $dy + $radius);

        $largeArc = 1;
        $sweep = $degrees < 0 ? 0 : 1;

        $description = implode(' ', [
            "M", $this->x, $this->y,
            "a", $radius, $radius, 0, $largeArc, $sweep, $endX, $endY,
        ]);

        $this->pushInstruction('path', ['d' => $description, 'class' => $this->tool]);

        return $this;
    }

    public function ellipse(int $x, int $y, int $x1, int $y1, int $x2, int $y2): PlotBuilder
    {
        // TODO: Implement ellipse() method.
    }

    public function curve(int $x, int $y, int $x1, int $y1, ...$points): PlotBuilder
    {
        // TODO: Implement curve() method.
    }

}
