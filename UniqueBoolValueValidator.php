<?php
/**
 * UniqueBoolValueValidator.php
 *
 * This validator class validates that for the given $object, only a single record in the DB
 * with $attribute_name = $single_bool_value should exists in the DB. Where is ti useful? This is best
 * answered in an example: suppose you have polls system and only one 'poll' can be 'published on frontpage'.
 * This property (published on front page) is represented in the DB in the form of some boolean column. Using
 * this validator you can make sure that only one such poll is always "promoted to front page". This validator
 * will not alter the existing poll's 'promoted' field - that's another business logic not handled here.
 *
 * Algorithmly speaking, this means that:
 * 1) if $object->$attribute != $single_single_bool_value, then NO validation error is flagged (and no DB check
 * 		is performed - no need to).
 * 2) if $object->$attribute == $single_single_bool_value, then a check against the DB is made to
 *      verify that no other record exists with this value ($single_bool_value).
 *
 * IMPORTANT LIMITATIONS:
 * ----------
 * 1) Concurrency issues are not handled at all. No locking is done, no isolation levels, nothing.
 * 2) This validator accepts and supports $object of type CActiveRecord ONLY. This is natural since the validator
 * 		checks stuff in the DB so this is relevant for "DB related CModels", meaning CActiveRecord objects.
 *
 * @license:
 * Copyright (c) 2012, Boaz Rymland
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of conditions and the following
 *      disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following
 *      disclaimer in the documentation and/or other materials provided with the distribution.
 * - The names of the contributors may not be used to endorse or promote products derived from this software without
 *      specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
class UniqueBoolValueValidator extends CValidator {

	/* @var bool $single_bool_value this boolean value (either true or false) should be unique in the DB.
	 *         defaults to true meaning we'd default-ly check that the object given, in the noted attribute,
	 *         if true, is the single such object in the DB with this attribute set to true (description of
	 *         this validator class in other words :) */
	public $single_bool_value = true;

	/* @var bool $enableClientValidation disable client side validation */
	public $enableClientValidation = false;

	/**
	 * validates $attribute in $object.
	 *
	 * @param CActiveRecord $object the object to check
	 * @param string $attribute the attribute name to validate in the given $object.
	 *
	 * @throws CException
	 */
	protected function validateAttribute($object, $attribute) {
		/* @var CActiveRecord $object */

		if (!is_a($object, "CActiveRecord")) {
			throw new CException("Only objects of type CActiveRecord are supported, " . get_type($object) . " given.");
		}

		/* first, check if the object passed contains an attribute with the special case of a value that we
					 need a single record of this type with this value.
				 */
		if ($object->$attribute != $this->single_bool_value) {
			// no need to check in the DB and no errors
			return;
		}

		$counter = $object->countByAttributes(array($attribute => $this->single_bool_value));
		if (((int)$counter) > 0) {
			Yii::log("found " . (int)$counter . " records of type " . get_class($object) . " exists with attribute $attribute with value of {$this->single_bool_value}. Therefore validation failed.", CLogger::LEVEL_INFO, __METHOD__);
			$this->addError($object, $attribute, Yii::t("application", "There's already a {class_name} with '{attribute}' set to '{unique_bool}'. Please update the other one and try again.", array('{class_name}' => get_class($object), '{attribute}' => $attribute, '{unique_bool}' => $this->single_bool_value)));
		}
	}
}
