<?php

/**
 * Note documentation for this Validator can be found here: https://validator.particle-php.com/en/latest/
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Validators;

use OpenEMR\Validators\BaseValidator;
use Particle\Validator\Exception\InvalidValueException;
use Particle\Validator\Validator;

class LibraryAssetBlobValidator extends BaseValidator
{
    protected function configureValidator()
    {
        parent::configureValidator();

        // insert validations
        $this->validator->context(
            self::DATABASE_INSERT_CONTEXT,
            function (Validator $context) {
                $context->required('title')->lengthBetween(1, 64);
                $context->required('description')->lengthBetween(1, 255);
                $context->required('type')->inArray(['article', 'assignment']);
                $context->required('content')->lengthBetween(1, 65535); // maximum for text field
                $context->required('originalCreator')->lengthBetween(1, 255);
                $context->optional('creatorLink')->lengthBetween(1, 255);
                $context->optional('journal')->lengthBetween(1, 65535); // maximum for text field
                $context->optional('tags')->callback(function ($value) {
                    if (!is_array($value)) {
                        throw new InvalidValueException('tags must be an array');
                    }
                    foreach ($value as $tag) {
                        if (!is_string($tag)) {
                            throw new InvalidValueException('tags must be an array of strings');
                        }
                        $strlen = strlen($tag);
                        if ($strlen < 1 || $strlen > 45) {
                            throw new InvalidValueException('tags must be between 1 and 45 characters');
                        }
                    }
                    return true;
                });
            }
        );

        // update validations copied from insert
        $this->validator->context(
            self::DATABASE_UPDATE_CONTEXT,
            function (Validator $context) {
                $context->copyContext(
                    self::DATABASE_INSERT_CONTEXT,
                    function ($rules) {
                        foreach ($rules as $key => $chain) {
                            $chain->required(false);
                        }
                    }
                );
                // additional muuid validation
                $context->required("id")->numeric();
            }
        );
    }
}
