<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupU2fBundle\Dto;

use JsonSerializable;

final class RegisterRequest implements JsonSerializable
{
    /**
     * @var string
     */
    public $version;

    /**
     * @var string
     */
    public $challenge;

    /**
     * @var string
     */
    public $appId;

    public function jsonSerialize()
    {
        // The array keys below conform to the U2F JavaScript RegisterRequest dictionary
        // https://fidoalliance.org/specs/fido-u2f-v1.0-nfc-bt-amendment-20150514/fido-u2f-javascript-api.html
        //     #dictionary-registerrequest-members
        return [
            'version'   => $this->version,
            'challenge' => $this->challenge,
            'appId'     => $this->appId,
        ];
    }
}