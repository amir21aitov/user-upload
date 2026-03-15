<?php

namespace App\Enums;

abstract class HttpStatus
{
    final public static function status(HttpCode $code): string
    {
        switch ($code) {
            // 1xx
            case HttpCode::CONTINUE:                                return 'Continue';
            case HttpCode::SWITCHING_PROTOCOLS:                     return 'Switching Protocols';
            case HttpCode::PROCESSING:                              return 'Processing';
            // 2xx
            case HttpCode::OK:                                      return 'Ok';
            case HttpCode::CREATED:                                 return 'Created';
            case HttpCode::ACCEPTED:                                return 'Accepted';
            case HttpCode::NON_AUTHORITATIVE_INFORMATION:           return 'Non-Authoritative Information';
            case HttpCode::NO_CONTENT:                              return 'No Content';
            case HttpCode::RESET_CONTENT:                           return 'Reset Content';
            case HttpCode::PARTIAL_CONTENT:                         return 'Partial Content';
            case HttpCode::MULTI_STATUS:                            return 'Multi-Status';
            // 3xx
            case HttpCode::MULTIPLE_CHOICES:                        return 'Multiple Choices';
            case HttpCode::MOVED_PERMANENTLY:                       return 'Moved Permanently';
            case HttpCode::FOUND:                                   return 'Found';
            case HttpCode::SEE_OTHER:                               return 'See Other';
            case HttpCode::NOT_MODIFIED:                            return 'Not Modified';
            case HttpCode::USE_PROXY:                               return 'Use Proxy';
            case HttpCode::TEMPORARY_REDIRECT:                      return 'Temporary Redirect';
            // 4xx
            case HttpCode::BAD_REQUEST:                             return 'Bad Request';
            case HttpCode::UNAUTHORIZED:                            return 'Unauthorized';
            case HttpCode::PAYMENT_REQUIRED:                        return 'Payment Required';
            case HttpCode::FORBIDDEN:                               return 'Forbidden';
            case HttpCode::NOT_FOUND:                               return 'Not Found';
            case HttpCode::METHOD_NOT_ALLOWED:                      return 'Method Not Allowed';
            case HttpCode::NOT_ACCEPTABLE:                          return 'Not Acceptable';
            case HttpCode::PROXY_AUTHENTICATION_REQUIRED:           return 'Proxy Authentication Required';
            case HttpCode::REQUEST_TIMEOUT:                         return 'Request Timeout';
            case HttpCode::CONFLICT:                                return 'Conflict';
            case HttpCode::GONE:                                    return 'Gone';
            case HttpCode::LENGTH_REQUIRED:                         return 'Length Required';
            case HttpCode::PRECONDITION_FAILED:                     return 'Precondition Failed';
            case HttpCode::REQUEST_ENTITY_TOO_LARGE:                return 'Request Entity Too Large';
            case HttpCode::REQUEST_URI_TOO_LONG:                    return 'Request-URI Too Long';
            case HttpCode::UNSUPPORTED_MEDIA_TYPE:                  return 'Unsupported Media Type';
            case HttpCode::REQUESTED_RANGE_NOT_SATISFIABLE:         return 'Requested Range Not Satisfiable';
            case HttpCode::EXPECTATION_FAILED:                      return 'Expectation Failed';
            case HttpCode::AUTHENTICATION_TIMEOUT_NOT_IN_RFC_2616:  return 'Authentication Timeout (not in RFC 2616)';
            case HttpCode::UNPROCESSABLE_ENTITY:                    return 'Unprocessable Entity';
            case HttpCode::LOCKED:                                  return 'Locked';
            case HttpCode::FAILED_DEPENDENCY:                       return 'Failed Dependency';
            case HttpCode::UPGRADE_REQUIRED:                        return 'Upgrade Required';
            // 5xx
            case HttpCode::INTERNAL_SERVER_ERROR:                   return 'Internal Server Error';
            case HttpCode::NOT_IMPLEMENTED:                         return 'Not Implemented';
            case HttpCode::BAD_GATEWAY:                             return 'Bad Gateway';
            case HttpCode::SERVICE_UNAVAILABLE:                     return 'Service Unavailable';
            case HttpCode::GATEWAY_TIMEOUT:                         return 'Gateway Timeout';
            case HttpCode::HTTP_VERSION_NOT_SUPPORTED:              return 'HTTP Version Not Supported';
            case HttpCode::VARIANT_ALSO_NEGOTIATES:                 return 'Variant Also Negotiates';
            case HttpCode::INSUFFICIENT_STORAGE:                    return 'Insufficient Storage';
            case HttpCode::BANDWIDTH_LIMIT_EXCEEDED:                return 'Bandwidth Limit Exceeded';
            case HttpCode::NOT_EXTENDED:                            return 'Not Extended';

            default:                                                return 'Unknown HttpCode';
        }
    }
}
