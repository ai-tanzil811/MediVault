<?php
/**
 * API Response Formatter
 */

class Response {

    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        return json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public static function error($error, $code = 400, $details = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'error' => $error,
            'code' => $code
        ];

        if ($details) {
            $response['details'] = $details;
        }

        return json_encode($response);
    }

    public static function paginated($data, $page, $limit, $total, $message = 'Success') {
        http_response_code(200);
        return json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public static function conflict($conflicts, $message = 'Drug conflicts detected') {
        http_response_code(200);
        return json_encode([
            'success' => true,
            'message' => $message,
            'hasConflicts' => count($conflicts) > 0,
            'conflicts' => $conflicts
        ]);
    }
}

?>
