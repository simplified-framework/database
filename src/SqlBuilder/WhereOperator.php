<?php
/**
 * Created by PhpStorm.
 * User: Andreas
 * Date: 24.12.2015
 * Time: 07:20
 */

namespace Simplified\Database\SqlBuilder;


class WhereOperator {
    const LESS_THAN = '<';
    const GREATER_THAN = '>';
    const EQUAL = '=';
    const NOT_EQUAL = '!=';
    const NOT = 'NOT';
    const IN = 'IN';
    const NOT_IN = 'NOT IN';
    const LIKE = 'LIKE';

    public static function isValid($op) {
        switch ($op) {
            case WhereOperator::LESS_THAN:
            case WhereOperator::GREATER_THAN:
            case WhereOperator::EQUAL:
            case WhereOperator::NOT_EQUAL:
            case WhereOperator::NOT:
            case WhereOperator::IN:
            case WhereOperator::NOT_IN:
            case WhereOperator::LIKE:
                return true;
        }
        return false;
    }
}