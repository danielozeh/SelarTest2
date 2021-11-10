<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionTransaction;

use Illuminate\Http\Request;
use Validator;

class ReportController extends Controller
{
    public function spoolReport(Request $request) {
        $validator = Validator::make($request->all(),[
            'start_date' => 'required|string',
            'end_date' => 'required|string'
        ]);

        if($validator->fails()) {
            return response()->json(['status' => 'failed', $validator->errors()]);
        }

        $from_date = date($request->from_date);
        $to_date = date($request->end_date);

        $reports = SubscriptionTransaction::with('user')->with('plan')->whereBetween('created_at', [$request->start_date, $request->end_date])->get();

        $new_sub_amount_naira = array();
        $new_sub_profit_naira = array();
        $new_sub_amount_usd = array();
        $new_sub_profit_usd = array();

        $rec_sub_amount_naira = array();
        $rec_sub_profit_naira = array();
        $rec_sub_amount_usd = array();
        $rec_sub_profit_usd = array();

        foreach ($reports as $report) {
            if($report['currency'] == "NGN" && $report['type'] == 'new_subscription') {
                array_push($new_sub_amount_naira, $report['amount']);
                array_push($new_sub_profit_naira, $report['selar_profit']);
            }

            if($report['currency'] == "USD"  && $report['type'] == 'new_subscription') {
                array_push($new_sub_amount_usd, $report['amount']);
                array_push($new_sub_profit_usd, $report['selar_profit']);
            }

            if($report['currency'] == "NGN" && $report['type'] == 'subscription_renewal') {
                array_push($rec_sub_amount_naira, $report['amount']);
                array_push($rec_sub_profit_naira, $report['selar_profit']);
            }

            if($report['currency'] == "USD"  && $report['type'] == 'subscription_renewal') {
                array_push($rec_sub_amount_usd, $report['amount']);
                array_push($rec_sub_profit_usd, $report['selar_profit']);
            }
        }

        $new_sub_amount_naira_sum = array_sum($new_sub_amount_naira);
        $new_sub_profit_naira_sum = array_sum($new_sub_profit_naira);

        $new_sub_amount_usd_sum = array_sum($new_sub_amount_usd);
        $new_sub_profit_usd_sum = array_sum($new_sub_profit_usd);

        $new_naira_subscriptions = [
            'amount' => number_format($new_sub_amount_naira_sum, 2),
            'profit' => number_format($new_sub_profit_naira_sum, 2),
            'currency' => "NGN"
        ];

        $new_usd_subscriptions = [
            'amount' => number_format($new_sub_amount_usd_sum, 2),
            'profit' => number_format($new_sub_profit_usd_sum, 2),
            'currency' => "USD"
        ];

        //convert usd to naira
        $new_amount_in_naira = $this->convertCurrency($new_sub_amount_usd_sum, "USD", "NGN");
        $new_amount_in_naira = number_format($new_amount_in_naira + $new_sub_amount_naira_sum, 2);

        //convert naira to usd
        $new_amount_in_usd = $this->convertCurrency($new_sub_amount_naira_sum, "NGN", "USD");
        $new_amount_in_usd = number_format($new_amount_in_usd + $new_sub_amount_usd_sum, 2);



        $rec_sub_amount_naira_sum = array_sum($rec_sub_amount_naira);
        $rec_sub_profit_naira_sum = array_sum($rec_sub_profit_naira);

        $rec_sub_amount_usd_sum = array_sum($rec_sub_amount_usd);
        $rec_sub_profit_usd_sum = array_sum($rec_sub_profit_usd);

        $rec_naira_subscriptions = [
            'amount' => number_format($rec_sub_amount_naira_sum, 2),
            'profit' => number_format($rec_sub_profit_naira_sum,2),
            'currency' => "NGN"
        ];

        $rec_usd_subscriptions = [
            'amount' => number_format($rec_sub_amount_usd_sum, 2),
            'profit' => number_format($rec_sub_profit_usd_sum, 2),
            'currency' => "USD"
        ];

        //convert usd to naira
        $rec_amount_in_naira = $this->convertCurrency($rec_sub_amount_usd_sum, "USD", "NGN");
        $rec_amount_in_naira = number_format($rec_amount_in_naira + $rec_sub_amount_naira_sum,2);

        //convert naira to usd
        $rec_amount_in_usd = $this->convertCurrency($rec_sub_amount_naira_sum, "NGN", "USD");
        $rec_amount_in_usd = number_format($rec_amount_in_usd + $rec_sub_amount_usd_sum, 2);

        $data = [
            'new_subscriptions' => [
                'in_naira' => $new_naira_subscriptions,
                'in_usd' => $new_usd_subscriptions,
                'amount_in_naira' => $new_amount_in_naira,
                'amount_in_usd' => $new_amount_in_usd
            ],
            'recurring_subscriptions' => [
                'in_naira' => $rec_naira_subscriptions,
                'in_usd' => $rec_usd_subscriptions,
                'amount_in_naira' => $rec_amount_in_naira,
                'amount_in_usd' => $rec_amount_in_usd
            ],
            "all_transactions" => $reports
        ];
        return response()->json($data);
    }

    private function convertCurrency($amount,$from_currency,$to_currency){
        $apikey = 'd51fb741c533be259bb9';
      
        $from_Currency = urlencode($from_currency);
        $to_Currency = urlencode($to_currency);
        $query =  "{$from_Currency}_{$to_Currency}";
      
        // change to the free URL if you're using the free version
        $json = file_get_contents("https://free.currconv.com/api/v7/convert?q={$query}&compact=ultra&apiKey={$apikey}");
        $obj = json_decode($json, true);
      
        $val = floatval($obj["$query"]);
      
      
        $total = $val * $amount;
        return number_format($total, 2, '.', '');
    }
}
