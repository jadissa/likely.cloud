<?php
namespace service\api;

class request {

    private $route;

    private $type;


    public function __construct( $SETTINGS, $route = '/', $type = 'GET', $DATA = [] ) {

        if( empty( $SETTINGS) ) {

            throw new \Exception( __NAMESPACE__ . '\\\\' . __CLASS__ . '\\\\' . __METHOD__ . ' SETTINGS missing' );

        }

        $this->setRoute( $SETTINGS, $route );

        $this->setType( $type );

        $this->setData( $DATA );

    }


    private function setRoute( $SETTINGS, $route = null ) {

        $this->route    = $API_URL = ( !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' ) . $SETTINGS->api . $route;

    }


    private function setType( $type = null ) {

        $this->type = $type;

    }


    private function setData( $DATA = [] ) {

        $this->DATA = $DATA;

    }


    public function parse() {

        $RESPONSE   = new \stdClass;

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $this->route );

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        if( strtolower( $this->type ) == 'post' ) {

            curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $this->DATA ) );

        }

        $RESPONSE->raw_response                   = curl_exec( $ch );

        $RESPONSE->http_status                    = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        curl_close( $ch );

        $RESPONSE->response                       = json_decode( $RESPONSE->raw_response, false );

        $RESPONSE->json_last_error                = json_last_error();

        if( !empty( $RESPONSE->json_last_error ) ) {

            $RESPONSE->json_last_error_message    = json_last_error_msg();

            $RESPONSE->stat                       = false;

        }

        #$RESPONSE->stat                           = true;

        return $RESPONSE;

    }

}