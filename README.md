# palmkash_ussd
$result =array(
  "status"=>"success",
  "result" =>array(
  array("id"=>13,
"station"=>"Rusoomo",
   ),
array(
"id"=>17,
"station"=>"Rusizi",
)
));
results ////

{
  "status": "success",
  "result": [
    {
      "id": 13,
      "station": "Rusoomo"
    },
    {
      "id": 17,
      "station": "Rusizi"
    }
  ]
}


//GetBookingTimes

{
"route_id" : 1,
"route_type" : "stop_over_route",
"amount" : 2100,
"number_of_tickets" : 1,
"msisdn": "25670XXXXX",
"name" : "Raymond Byaru",
"date_of_travel" : "2020-04-20" // we have been using currentdate 
}

//Test  data sample below

curl -X POST http://{HOST_URL}/{MNO_URI} \
  -H "Content-Type: application/xml" \
  -d '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<request type="pull">
  <subscriberInput>1</subscriberInput>
  <sessionId>2201606926351</sessionId>
  <msisdn>2507813XXXX</msisdn>
  <newRequest>0</newRequest>
  <parameters>
  </parameters>
  <freeflow>
    <mode>FE</mode>
  </freeflow>
</request>'
