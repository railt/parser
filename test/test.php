<?php

use Railt\Io\File;
use Railt\Lexer\Driver\NativeStateful;
use Railt\Parser\Parser;

require __DIR__ . '/../vendor/autoload.php';

$rules = require __DIR__ . '/rules.php';
$pcre = require __DIR__ . '/lex.php';

$lexer = new NativeStateful($pcre, ['T_WHITESPACE', 'T_COMMENT', 'T_COMMA']);

$parser = new Parser($lexer);

foreach ($rules as $rule) {
    $parser->add($rule);
}

echo $parser->parse(File::fromSources('

interface Pagination($values: A) implements B(a: C(s: D))
    @interfaceDirective
{
    values: [$values!]!
        @interfaceFieldDirective
}

type LengthAwarePaginator($values: Object) implements Pagination(values: $values)
    @typeDirective
    @typeDirective2
{
    perPage(
        a: Argument,
            @typeArgumentDirective
        b: Argument2(c: Argument3)
            @typeArgumentDirective2
    ): Field!
        @typeFieldDirective
    count: Field2!
    values: [$values!]!
}

type Repository($typeOf: Object, $paginator: Pagination) {
    findAll: $paginator(values: $typeOf)!
    find(id: ID!): $typeOf
}

type Query {
    users: Repository(typeOf: User, paginator: LengthAwarePaginator)
}

'));
