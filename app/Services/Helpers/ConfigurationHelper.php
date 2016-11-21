<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Services\Helpers;

/**
 * Description of ConfigurationHelper
 *
 * @author USER
 */
class ConfigurationHelper
{
    public function editConfiguration(array $input)
    {
        $data                                                                 = [];
        isset($input['time_tolerance']) ? $data['time_tolerance']             = $input['time_tolerance'] : null;
        isset($input['time_restriction']) ? $data['time_restriction']         = $input['time_restriction'] : null;
        isset($input['max_people']) ? $data['max_people']                     = $input['max_people'] : null;
        isset($input['max_table']) ? $data['max_table']                       = $input['max_table'] : null;
        isset($input['res_code_status']) ? $data['res_code_status']           = $input['res_code_status'] : null;
        isset($input['res_privilege_status']) ? $data['res_privilege_status'] = $input['res_privilege_status'] : null;
        // isset($input['messenger_status']) ? $data['messenger_status'] = $input['messenger_status'] : null;
        // isset($input['user_add']) ? $data['user_add']                 = $input['user_add'] : null;
        // isset($input['user_upd']) ? $data['user_upd']                 = $input['user_upd'] : null;
        // isset($input['reserve_portal']) ? $data['reserve_portal']     = $input['reserve_portal'] : null;
        isset($input['res_percentage_id']) ? $data['res_percentage_id'] = $input['res_percentage_id'] : null;
        isset($input['name_people_1']) ? $data['name_people_1']         = $input['name_people_1'] : null;
        isset($input['name_people_2']) ? $data['name_people_2']         = $input['name_people_2'] : null;
        isset($input['name_people_3']) ? $data['name_people_3']         = $input['name_people_3'] : null;
        isset($input['status_people_1']) ? $data['status_people_1']     = $input['status_people_1'] : null;
        isset($input['status_people_2']) ? $data['status_people_2']     = $input['status_people_2'] : null;
        isset($input['status_people_3']) ? $data['status_people_3']     = $input['status_people_3'] : null;

        return $data;
    }
}
