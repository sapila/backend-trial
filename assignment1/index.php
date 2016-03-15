<?php

// Load database connection, helpers, etc.
require_once(__DIR__ . '/errors.php');
require_once(__DIR__ . '/include.php');

// Vars
$period = 12; // Life-Time of 12 months
$commission = 0.10; // 10% commission

// Prepare query
$result = $db
	->prepare('
		SELECT * FROM bookers limit 10000
	')
	->run()
;
?>
<!doctype html>
<html>
	<head>
		<title>Assignment 1: Create a Report (SQL)</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<style type="text/css">
			.report-table
			{
				width: 100%;
				border: 1px solid #000000;
			}
			.report-table td,
			.report-table th
			{
				text-align: left;
				border: 1px solid #000000;
				padding: 5px;
			}
			.report-table .right
			{
				text-align: right;
			}
		</style>
	</head>
	<body>
		<h1>Report:</h1>
		<table class="report-table">
			<thead>
				<tr>
					<th>Start</th>
					<th>Bookers</th>
					<th># of bookings (avg)</th>
					<th>Turnover (avg)</th>
					<th>LTV</th>
				</tr>
			</thead>
			<tbody>   
                 <?php 
        class Booker {
           public $first_booking;
           public $turnover_sum = 0;
           public $bookitems_count = 0;
        }
        $bookers =array();
         ?>  
		

        <?php  $resultBookers = $db
            //get all booking items for each booker
            ->prepare('
                SELECT bookers.id,bookingitems.end_timestamp,bookingitems.locked_total_price FROM bookers 
                  INNER JOIN bookings ON (bookers.id=bookings.booker_id) 
                  INNER JOIN bookingitems ON (bookings.id=bookingitems.booking_id)
                  ORDER BY bookingitems.end_timestamp  
            ')
            ->run();
          
            
            $count=0;$turnover=0;$bookings_sum=0;
            
            $tempid ;$end_date;
            $first = true;
            $booker = new Booker();
            $rows=0;
            foreach ($resultBookers as $bookersIndex => $bookersRow):

              if($bookersIndex == 0)
              {
                $tempid = $bookersRow->id;
              }

             

              if($tempid == $bookersRow->id)
              {
                if($first == true){
                //save date of first booking
                  $booker->first_booking = $bookersRow->end_timestamp;
                  $end_date = strtotime("+".$period." month", $booker->first_booking);
                  // echo date('m Y',$booker->first_booking)." date  ".date('m Y',$end_date)." ".$tempid."<br>";
                  $first = false;
                }
              //if booking item is between the period
              if($bookersRow->end_timestamp <= $end_date)
               {
                   $booker->turnover_sum += $bookersRow->locked_total_price;
                   $booker->bookitems_count++;
                } 
                //echo $bookersRow->id.' '.date('m Y',$bookersRow->end_timestamp).' '.$bookersRow->locked_total_price.'<br>'; 
            }else{

              //new booker id
               if($booker->bookitems_count>0)
                $bookers[] = $booker;
               $booker = new Booker();

              $tempid = $bookersRow->id;
              
                //save date of first booking
                $booker->first_booking = $bookersRow->end_timestamp;
                        $end_date = strtotime("+".$period." month", $booker->first_booking);
                       // echo date('m Y',$booker->first_booking)." date  ".date('m Y',$end_date)."<br>";
                       
               
              //if booking item is between the period
              if($bookersRow->end_timestamp <= $end_date)
               {
                   $booker->turnover_sum += $bookersRow->locked_total_price;
                   $booker->bookitems_count++;
                } 
            }

          
           

             $rows++;
             // echo 'Results : '.$booker->bookitems_count.' count : '.$booker->turnover_sum.' sum date:'.date('m Y',$booker->first_booking).'<br>';
            endforeach;
              
           

           
        ?>
    
 
					
				<?php 
                $i;$mindate=$bookers[0]->first_booking;$maxdate=$bookers[0]->first_booking;
                //find min/max dates of bookings
                for($i=0;$i<sizeof($bookers);$i++)
                {
                    if($bookers[$i]->first_booking<$mindate)
                        $mindate = $bookers[$i]->first_booking;
                    if($bookers[$i]->first_booking>$mindate)
                        $maxdate = $bookers[$i]->first_booking;
                        
                } 

                //for every month between min/max date of bookingitems
                $count=0;$turnover=0;$bookings_sum=0;
                while($mindate<=$maxdate)
                {
                      for($i=0;$i<sizeof($bookers);$i++)
                        {
                            //sum data for every bookingitem of the month
                            if(date('m Y',$bookers[$i]->first_booking)==date('m Y',$mindate))
                              {
                                  $bookings_sum+=$bookers[$i]->bookitems_count;
                                  $turnover+=$bookers[$i]->turnover_sum;
                                  $count++;
                              }       
                        } ?>
                        
                    <tr>
          						<td><?php echo date('m Y',$mindate) ?></td>
          						<td><?php echo $count ?></td>
          						<td><?php echo ($bookings_sum/$period)?></td>
          						<td><?php echo ($turnover/$period)?></td>
          						<td><?php echo ($turnover/$period)*$commission?></td>
                                  
          					</tr>
                    
                  <?php

                      $count=0;$turnover=0;$bookings_sum=0;
                      //go to next month
                      $mindate = strtotime("+1 month", $mindate);
                }
                
                ?>
       
			</tbody>
			<tfoot>
				<tr>
					<td colspan="4" class="right"><strong>Total rows:</strong></td>
					<td><?php echo $rows; ?></td>
				</tr>
			</tfoot>
		</table>
	</body>
</html>