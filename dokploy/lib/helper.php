<?php
use Illuminate\Database\Capsule\Manager as Capsule;

function saveServiceCustomField($serviceId, $fieldName, $value, $productId = null)
{
    if (empty($serviceId) || empty($fieldName)) {
        return false;
    }

    if (!$productId) {
        $hosting = Capsule::table('tblhosting')
            ->where('id', $serviceId)
            ->first();

        if (!$hosting) {
            return false;
        }

        $productId = $hosting->packageid;
    }

    try {
        $field = Capsule::table('tblcustomfields')
            ->where('fieldname', $fieldName)
            ->where('relid', $productId)
            ->where('type', 'product')
            ->first();

        if (!$field) {
            $fieldId = Capsule::table('tblcustomfields')->insertGetId([
                'type'      => 'product',
                'relid'     => $productId,
                'fieldname' => $fieldName,
                'fieldtype' => 'text',
                'adminonly' => 1,
                'required'  => 0,
                'showorder' => 0,
                'showinvoice' => 0,
                'description' => '',
                'sortorder' => 0,
            ]);
            delete_query("tblconfiguration", ["setting" => "CustomFieldCache"]);
        } else {
            $fieldId = $field->id;
        }
        
        $existingValue = Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', $fieldId)
            ->where('relid', $serviceId)
            ->first();

        if ($existingValue) {
            Capsule::table('tblcustomfieldsvalues')
                ->where('id', $existingValue->id)
                ->update(['value' => $value]);
        } else {
            Capsule::table('tblcustomfieldsvalues')->insert([
                'fieldid' => $fieldId,
                'relid'   => $serviceId,
                'value'   => $value,
            ]);
        }

        return true;

    } catch (Exception $e) {
        logModuleCall(
            'dokploy',
            'SaveServiceCustomField',
            ['serviceId' => $serviceId, 'field' => $fieldName, 'value' => $value],
            $e->getMessage()
        );
        return false;
    }
}
