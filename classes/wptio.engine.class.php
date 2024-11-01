<?php
namespace wptio;
if ( ! defined( 'ABSPATH' ) ) {exit;}
class wptio_engine
{
    public static function wptio_send_data_to_client($data, $repsonseType, $isOk = true, $forceDataAsArray = false)
    {
        if ($repsonseType == 'json') {
            header('Content-Type: application/json');
        } elseif ($repsonseType == 'html') {
            header('Content-Type:text/html; charset=UTF-8"');
        } elseif ($repsonseType == 'text') {
            header('Content-Type:text/plain; charset=UTF-8"');
        } else {
            header('Content-Type:text/html; charset=UTF-8"');
        }

        if (is_array($data) && !$forceDataAsArray) {
            $data = ( object )$data;
        }

        $dataToSend = ( object )array(
            'result' => $isOk ? 'ok' : 'not ok!',
            'theData' => $data
        );

        $json_data = json_encode($dataToSend);

        echo $json_data;
        exit ();
    }

    public static function wptio_validate_request($requestData)
    {
        $errors = array();

        if (!isset ($requestData->domain)) {
            $errors [] = 'Domain is missing';
        }
        if (!isset ($requestData->datasource)) {
            $errors [] = 'Data Source is missing';
        }
        if (!isset ($requestData->datasegment)) {
            $errors [] = 'Data Segment is missing';
        }
        if (!isset ($requestData->params)) {
            $errors [] = 'Params is missing';
        }

        if (sizeof($errors) > 0) {
            $repsonseType = 'json';

            if (isset ($requestData->repsonseType)) {
                $repsonseType = $requestData->repsonseType;
            }

            self::wptio_send_data_to_client(( object )array(
                'message' => 'invalid request',
                "details" => $errors
            ), $repsonseType, false);
        }
    }

    public static function wptio_process_request($requestData)
    {

        $namespace = str_replace('-', '_', $requestData->domain);
        $class_name = str_replace('-', '_', $requestData->datasource);
        $function_name = str_replace('-', '_', $requestData->datasegment);
        $params = $requestData->params;

        $repsonseType = 'json';

        if (isset ($requestData->repsonseType)) {
            $repsonseType = $requestData->repsonseType;
        }

        if (!empty ($namespace)) {
            $class_name = sprintf('%1$s\\%2$s', $namespace, $class_name);
        }

        if (!class_exists($class_name)) {
            $error = ( object )array(
                'message' => 'data source not defined!',
                "details" => sprintf('%1$s%2$s%3$s', $namespace, (empty ($namespace) ? '' : '\\'), $class_name)
            );
            self::wptio_send_data_to_client($error, $repsonseType, FALSE);
            return;
        }

        $objectinstance = new $class_name ();

        if (!method_exists($objectinstance, $function_name)) {
            $error = ( object )array(
                'message' => 'data segment not defined!',
                "details" => sprintf('%1$s%2$s%3$s\\%4$s', $namespace, (empty ($namespace) ? '' : '\\'), $class_name, $function_name)
            );
            self::wptio_send_data_to_client($error, $repsonseType, FALSE);
            return;
        }

        try {
            $data = $objectinstance->$function_name ($params);
            self::wptio_send_data_to_client($data, $repsonseType);
        } catch (Exception $e) {
            $error = ( object )array(
                'message' => 'Caught exception!',
                "details" => $e->getMessage()
            );
            self::wptio_send_data_to_client($error, $repsonseType, FALSE);
        }
    }
}