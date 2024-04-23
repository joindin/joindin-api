<?php

namespace Joindin\Api\Model;

use Joindin\Api\Request;

/**
 * Object that represents a token
 *
 * @property int $id
 */
class TokenModel extends BaseModel
{
    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    public function getDefaultFields(): array
    {
        return [
            'token'             => 'id',
            'application'       => 'application',
            'created_date'      => 'created_date',
            'last_used_date'    => 'last_used_date',
            'application_owner' => 'full_name',
        ];
    }

    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    public function getVerboseFields(): array
    {
        return $this->getDefaultFields();
    }

    /**
     * Return this object with client-facing fields and hypermedia, ready for output
     *
     * @param Request $request
     * @param bool $verbose
     *
     * @return array
     */
    public function getOutputView(Request $request, bool $verbose = false): array
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
