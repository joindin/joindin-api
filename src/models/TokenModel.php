<?php

/**
 * Object that represents a token
 */
class TokenModel extends AbstractModel
{
    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    public function getDefaultFields()
    {
        return array(
            'token' => 'id',
            'application' => 'application',
            'created_date'  => 'created_date',
            'last_used_date'  => 'last_used_date',
            'application_owner' => 'full_name',
        );
    }

    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    public function getVerboseFields()
    {
        return $this->getDefaultFields();
    }

    /**
     * Return this object with client-facing fields and hypermedia, ready for output
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        $item = parent::getOutputView($request, $verbose);

        $item['token_uri'] = sprintf(
            '%1$s/%2$s/token/%3$s',
            $request->base,
            $request->version,
            $this->id
        );

        return $item;
    }
}
