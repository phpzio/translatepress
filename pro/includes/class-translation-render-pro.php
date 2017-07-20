<?php


class TRP_Translation_Render_Pro extends TRP_Translation_Render{
    protected function start_output_buffering(){
        return true;
    }
}