<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\res_turn_calendar;
class CalendarServiceTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $calendar = res_turn_calendar::where('res_turn_id', 10000)->get()->count();        
        if($calendar > 0){
            $this->assertTrue(true);
        }else{
            $this->assertTrue(false);
        }
        
    }
}
