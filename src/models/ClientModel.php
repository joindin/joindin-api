<?php

/**
 * Object that represents a talk.
 */
class ClientModel extends BaseModel
{
    /**
     * Default fields in the output view.
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    public function getDefaultFields()
    {
        return [
            'consumer_key' => 'consumer_key',
            'created_date' => 'created_date',
            'application'  => 'application',
            'description'  => 'description',
            'callback_url' => 'callback_url',
        ];
    }

    /**
     * Default fields in the output view.
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    public function getVerboseFields()
    {
        $fields = $this->getDefaultFields();

        $fields['consumer_secret'] = 'consumer_secret';
        $fields['user_id'] = 'user_id';

        return $fields;
    }

    /**
     * Return this object with client-facing fields and hypermedia, ready for output.
     *
     * @param Request $request
     * @param bool    $verbose
     *
     * @return array
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        $item = parent::getOutputView($request, $verbose);

        $item['client_uri'] = sprintf(
            '%1$s/%2$s/applications/%3$s',
            $request->base,
            $request->version,
            $this->id
        );

        return $item;
    }
}
