<?php
//
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
  /**
  * Benchmark::Timer
  *
  * Example:
  *
  *   $timer = new Benchmark_Timer;
  *
  *   $timer->start();
  *   $timer->stop();
  *   $time = $timer->get_timer();
  *
  */

  class Benchmark_Timer
  {
    var $timers = array();
    var $precision = 4;


    function start($timer = 'default')
    {
      $this->set_timer( $timer, "start" );
    }

    function stop($timer = 'default')
    {
      $this->set_timer( $timer, "stop" );
    }

    function set_timer( $timer, $point )
    {
      $microtime = explode( " ", microtime() );
      $this->timers[ $timer ] [$point] = $microtime[ 1 ] . substr( $microtime[ 0 ], 1 );
    }

    function get_timer( $timer = 'default')
    {
      $start = $this->timers[ $timer ] ['start'];
      $stop  = $this->timers[ $timer ] ['stop'];
      return $this->time_elapsed( $start, $stop);
    }

    function get_timers()
    {
      $result = array();
      while( list( $timer,  ) = each( $this->timers ) )
      {
          $result[ $timer  ] = $this->time_elapsed( $this->timers[ $timer ] ['start'], $this->timers[ $timer ] ['stop']);
      }
      return $result;
    }

    function time_elapsed( $start, $end )
    {
      return round( ($end - $start) , $this->precision );
    }


  }
?>
