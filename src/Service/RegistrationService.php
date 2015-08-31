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

namespace Surfnet\StepupU2fBundle\Service;

use Surfnet\StepupU2fBundle\Dto\RegisterRequest;
use Surfnet\StepupU2fBundle\Dto\RegisterResponse;
use Surfnet\StepupU2fBundle\Dto\Registration;
use Surfnet\StepupU2fBundle\Exception\LogicException;
use u2flib_server\Error;
use u2flib_server\RegisterRequest as YubicoRegisterRequest;
use u2flib_server\U2F;

final class RegistrationService
{
    /**
     * @var \u2flib_server\U2F
     */
    private $u2fService;

    public function __construct(U2F $u2fService)
    {
        $this->u2fService = $u2fService;
    }

    /**
     * @return RegisterRequest
     */
    public function requestRegistration()
    {
        /** @var YubicoRegisterRequest $yubicoRequest */
        list($yubicoRequest) = $this->u2fService->getRegisterData();

        $request = new RegisterRequest();
        $request->version   = $yubicoRequest->version;
        $request->challenge = $yubicoRequest->challenge;
        $request->appId     = $yubicoRequest->appId;

        return $request;
    }

    /**
     * @param RegisterRequest  $request The register request that you requested earlier and was used to query the U2F
     *     device.
     * @param RegisterResponse $response The response that the U2F device gave in response to the register request.
     * @return RegistrationVerificationResult
     */
    public function verifyRegistration(RegisterRequest $request, RegisterResponse $response)
    {
        if ($response->errorCode) {
            return RegistrationVerificationResult::deviceReportedError($response->errorCode);
        }

        $yubicoRequest = new YubicoRegisterRequest($request->challenge, $request->appId);

        try {
            $yubicoRegistration = $this->u2fService->doRegister($yubicoRequest, $response);
        } catch (Error $error) {
            switch ($error->getCode()) {
                case \u2flib_server\ERR_UNMATCHED_CHALLENGE:
                    return RegistrationVerificationResult::responseChallengeDidNotMatchRequestChallenge();
                case \u2flib_server\ERR_ATTESTATION_SIGNATURE:
                    return RegistrationVerificationResult::responseWasNotSignedByDevice();
                case \u2flib_server\ERR_ATTESTATION_VERIFICATION:
                    return RegistrationVerificationResult::deviceCannotBeTrusted();
                case \u2flib_server\ERR_PUBKEY_DECODE:
                    return RegistrationVerificationResult::publicKeyDecodingFailed();
                default:
                    throw new LogicException(
                        sprintf(
                            'The Yubico U2F service threw an exception with error code %d that should not occur ("%s")',
                            $error->getCode(),
                            $error->getMessage()
                        ),
                        $error
                    );
            }
        }

        $registration = new Registration();
        $registration->keyHandle = $yubicoRegistration->keyHandle;
        $registration->publicKey = $yubicoRegistration->publicKey;

        return RegistrationVerificationResult::success($registration);
    }
}
