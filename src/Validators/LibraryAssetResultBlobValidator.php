<?php

/**
 * Note documentation for this Validator can be found here: https://validator.particle-php.com/en/latest/
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Validators;

use OpenEMR\Validators\BaseValidator;
use Particle\Validator\Exception\InvalidValueException;
use Particle\Validator\Rule\Uuid;
use Particle\Validator\Validator;

class LibraryAssetResultBlobValidator extends BaseValidator
{
    protected function configureValidator()
    {
        parent::configureValidator();

        // insert validations
        $this->validator->context(
            self::DATABASE_INSERT_CONTEXT,
            function (Validator $context): void {
                $context->required('asset.id')->numeric();
                $context->required('answers')->isArray()->allowEmpty(true)->each(function (Validator $context): void {
                    $context->required('id')->uuid(Uuid::UUID_V4);
                    // the value can be anything
                });
                $context->required("assignmentItemId")->uuid(Uuid::UUID_V4);
                $context->required("clientId")->uuid(Uuid::UUID_V4);
            }
        );

        // update validations copied from insert
        $this->validator->context(
            self::DATABASE_UPDATE_CONTEXT,
            function (Validator $context): void {
                $context->copyContext(
                    self::DATABASE_INSERT_CONTEXT,
                    function ($rules): void {
                        foreach ($rules as $chain) {
                            $chain->required(false);
                        }
                    }
                );
                // additional muuid validation
                $context->required("id")->uuid(Uuid::UUID_V4);
            }
        );
    }
}
