<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Emailstatus;

class ApiController extends Controller
{
    public function awsSnsEndpoint(Request $request)
    {
        // TODO
        // Validasi data
        $dataSns = file_get_contents('php://input');
        // $dataSns2 = json_decode($dataSns, true);

        // $dataSns = \GuzzleHttp\json_decode($dataSns, true);
        $data = \GuzzleHttp\json_decode($dataSns, true);


        $message = \GuzzleHttp\json_decode($data['Message'], true);
        print_r($message);
        $type = $message['notificationType'];

        switch ($type){
            case "Bounce":
                $status = $message['bounce']['bounceType'];
                $email = $message['bounce']['bouncedRecipients'][0]['emailAddress'];
                $diagnostic = $message['bounce']['bouncedRecipients'][0]['diagnosticCode'];
                break;
            case "Complaint":
                $status = null;
                $email = $message['complaint']['complainedRecipients'][0]['emailAddress'];
                $diagnostic = $message['complaint']['complaintFeedbackType'];
                break;
            default:
                return false;
        }


var_dump($status);
var_dump($email);
var_dump($diagnostic);
die;
        // print_r($data);
        // print_r($dataSns2);
        // die;
        // print_r($data['TopicArn']);die;
        // $hai = json_encode($dataSns);
        // $hai2 = json_decode($hai);
// $dataPost = file_get_contents('php://input');

        // print_r($dataSns->Type);
        // die;
        if ($dataSns['TopicArn']) {
            $response = json_decode($dataSns['Message'], true);
            $log = [];
            if ($response['notificationType'] === 'Bounce') {
                $log['type'] = $response['notificationType'];
                $log['mail_from'] = $response['mail']['commonHeaders']['from'][0];
                $log['mail_to'] = $response['mail']['commonHeaders']['to'][0];
                $log['mail_subject'] = $response['mail']['commonHeaders']['subject'];
                $log['bounceType'] = $response['bounce']['bounceType'];
                $log['error_response'] = $response['bounce']['bouncedRecipients'][0]['diagnosticCode'];
                $log['timestamp'] = $response['mail']['timestamp'];
            }
            if ($response['notificationType'] === 'Complaint') {
                $log['type'] = $response['notificationType'];
                $log['mail_from'] = $response['mail']['commonHeaders']['from'][0];
                $log['mail_to'] = $response['mail']['commonHeaders']['to'][0];
                $log['mail_subject'] = $response['mail']['commonHeaders']['subject'];
                $log['timestamp'] = $response['mail']['timestamp'];
            }

            $simpan = Emailstatus::create($log);
        } else {
            die;
        }
    }
}
