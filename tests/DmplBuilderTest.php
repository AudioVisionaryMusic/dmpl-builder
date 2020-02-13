<?php

namespace Nielsiano\DmplBuilder\Tests;

use Nielsiano\DmplBuilder\DmplBuilder;
use PHPUnit\Framework\TestCase;

class DmplBuilderTest extends TestCase
{

    protected DmplBuilder $builder;

    public function setUp(): void
    {
        $this->builder = new DmplBuilder;
    }

    public function test_it_can_init_and_return_start_instructions()
    {
        $this->assertEquals(';: ECM,U H L0,A100,100,R,e', $this->builder->compile());
    }

    public function test_it_can_add_pen_up_to_dmpl_string()
    {
        $this->builder->penUp();
        $this->assertEquals(';: ECM,U H L0,A100,100,R,U,e', $this->builder->compile());
    }

    public function test_it_can_add_pen_down_to_dmpl_string()
    {
        $this->builder->penDown();
        $this->assertEquals(';: ECM,U H L0,A100,100,R,D,e', $this->builder->compile());
    }

    public function test_it_can_change_pen_tool_to_regular_cut()
    {
        $this->builder->regularCut();
        $this->assertEquals(';: ECM,U H L0,A100,100,R,P0;,e', $this->builder->compile());
    }

    public function test_it_can_change_pen_tool_to_flex_cut()
    {
        $this->builder->flexCut();
        $this->assertEquals(';: ECM,U H L0,A100,100,R,P6;,e', $this->builder->compile());
    }

    public function test_it_can_change_pressure_in_gram()
    {
        $this->builder->pressure(80);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,BP80;,e', $this->builder->compile());
    }

    public function test_it_can_change_velocity()
    {
        $this->builder->velocity(100);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,V100;,e', $this->builder->compile());
    }

    public function test_it_can_finalize_the_dmpl_string_without_cutoff()
    {
        $this->assertEquals(';: ECM,U H L0,A100,100,R,e', $this->builder->compile());
    }

    public function test_it_can_add_a_new_plot()
    {
        $this->builder->plot(-1000, 5000);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,-1000,5000,e', $this->builder->compile());
    }

    public function test_it_can_add_a_circle()
    {
        $this->builder->circle(600, 700, 800);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,CC 600,700,800,e', $this->builder->compile());
    }

    public function test_it_can_add_an_arc()
    {
        $this->builder->arc(400, 500, 600);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,CA 400,500,600,e', $this->builder->compile());
    }

    public function test_it_can_add_an_ellipse()
    {
        $this->builder->ellipse(400, 500, 600, 700, 800, 900);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,CE 400,500,600,700,800,900,e', $this->builder->compile());
    }

    public function test_it_can_add_a_general_curve()
    {
        $this->builder->curve(10, 20, 30, 40, 50, 60, 70, 80, 90, 100);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,CG 10,20,30,40,50,60,70,80,90,100,e', $this->builder->compile());
    }

    public function test_it_can_add_a_general_curve_with_flipped_axes()
    {
        $this->builder->flipAxes();
        $this->builder->curve(10, 20, 30, 40, 50, 60, 70, 80, 90, 100);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,CG 20,10,40,30,60,50,80,70,100,90,e', $this->builder->compile());
    }

    public function test_it_can_push_a_generic_command()
    {
        $this->builder->pushCommand('V10;');
        $this->assertEquals(';: ECM,U H L0,A100,100,R,V10;,e', $this->builder->compile());
    }

    public function test_it_can_chain_multiple_actions()
    {
        $this->builder->penUp()->regularCut()->penDown()->plot(-1984, 1337);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,U,P0;,D,-1984,1337,e', $this->builder->compile());
    }

    public function test_it_can_flip_axes()
    {
        $this->builder->flipAxes()->plot(-1, 2)->plot(1337, 1984);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,2,-1,1984,1337,e', $this->builder->compile());
    }

    public function test_it_can_finalize_the_dmpl_string_with_cutoff()
    {
        $this->assertEquals(';: ECM,U H L0,A100,100,R,;:c,e', $this->builder->cutOff()->compile());
    }

    public function test_it_can_change_pen()
    {
        $this->builder->changePen(3);
        $this->assertEquals(';: ECM,U H L0,A100,100,R,P3;,e', $this->builder->compile());
    }

    public function test_it_will_throw_exception_when_invalid_pen_is_chosen()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('[1984] is not a valid Pen.');
        $this->builder->changePen(1984);
    }

    public function test_it_can_change_measuring_unit()
    {
        $this->builder->setMeasuringUnit('M');
        $this->assertEquals(';: ECM,U H L0,A100,100,R,e', $this->builder->compile());
    }

    public function test_it_will_throw_exception_when_invalid_measuring_unit_is_chosen()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('[9] is not a valid measuring unit.');
        $this->builder->setMeasuringUnit(9);
    }

}
