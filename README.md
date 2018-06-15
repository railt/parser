<p align="center">
    <img src="https://railt.org/images/logo-dark.svg" width="200" alt="Railt" />
</p>

<p align="center">
    <a href="https://travis-ci.org/railt/parser"><img src="https://travis-ci.org/railt/parser.svg?branch=master" alt="Travis CI" /></a>
    <a href="https://scrutinizer-ci.com/g/railt/parser/?branch=master"><img src="https://scrutinizer-ci.com/g/railt/parser/badges/coverage.png?b=master" alt="Code coverage" /></a>
    <a href="https://scrutinizer-ci.com/g/railt/parser/?branch=master"><img src="https://scrutinizer-ci.com/g/railt/parser/badges/quality-score.png?b=master" alt="Scrutinizer CI" /></a>
    <a href="https://packagist.org/packages/railt/parser"><img src="https://poser.pugx.org/railt/parser/version" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/railt/parser"><img src="https://poser.pugx.org/railt/parser/v/unstable" alt="Latest Unstable Version"></a>
    <a href="https://raw.githubusercontent.com/railt/parser/master/LICENSE.md"><img src="https://poser.pugx.org/railt/parser/license" alt="License MIT"></a>
</p>

# Parser

The parser provides a set of components for grammar analysis (Parsing) of the source code 
and converting them into an abstract syntax tree (AST).

For the beginning it is necessary to familiarize with parsing algorithms. This implementation,
although it allows you to switch between runtime, but provides out of the box two 
implementations: [LL(1) - Simple and LL(k) - Lookahead](https://en.wikipedia.org/wiki/LL_parser).

In order to create your own parser we need:
1) Create lexer
2) Create grammar

## Lexer

Let's create a primitive lexer that can handle spaces, numbers and the addition character.

> More information about the lexer can be found in [this repository](https://github.com/railt/lexer).

```php
$lexer = new Railt\Lexer\Driver\NativeStateless();
$lexer->add('T_WHITESPACE', '\\s+', false); 
$lexer->add('T_NUMBER', '\\d+');
$lexer->add('T_PLUS', '\\+');
```

## Grammar

Grammar will be a little more complicated. We need to determine in what order 
the tokens in the source text can be located, which we will parse.

First we start with the [(E)BNF format](https://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_form):

```ebnf
(* A simple example of adding two numbers will look like this: *)
expr = T_NUMBER T_PLUS T_NUMBER ;
```

To define this rule inside the Parser, we simply use two classes that define the rules 
inside the product, this is the [concatenation](https://en.wikipedia.org/wiki/Concatenation) 
and definitions of the tokens.

```php
//
// This (e)BNF construction:
// expr = T_NUMBER T_PLUS T_NUMBER ;
// 
// Looks like:
// Concatenation1 = Token1 Token2 Token1
//
$parser = new Railt\Parser\Parser($lexer, [
    new Railt\Parser\Rule\Concatenation(0, [1, 2, 1], 'expr'),
    new Railt\Parser\Rule\Token(1, 'T_NUMBER'),
    new Railt\Parser\Rule\Token(2, 'T_PLUS'),
]);
```

In order to test the grammar, we can simply parse the source.

```php
echo $parser->parse(Railt\Io\File::fromSources('2 + 2'));
```

Will outputs:
```xml
<Ast>
    <Rule name="expr" offset="0">
        <Leaf name="T_NUMBER" offset="0">2</Leaf>
        <Leaf name="T_PLUS" offset="2">+</Leaf>
        <Leaf name="T_NUMBER" offset="4">2</Leaf>
    </Rule>
</Ast>
```

But if the source is wrong, the parser will tell you exactly where the error occurred:

```php
echo $parser->parse(Railt\Io\File::fromSources('2 + + 2'));
//                                                  ^
//
// throws "Railt\Parser\Exception\UnexpectedTokenException" with message: 
// "Unexpected token '+' (T_PLUS) at line 1 and column 5"
```

In addition, there are other grammar rules.

### Alternation 

Choosing between several rules.

```php
// EBNF: choice = some | any ;
new Alternation(<RULE_ID>, [<some_ID>, <any_ID>], 'choice');
```

### Concatenation 

Sequence of rules.

```php
// EBNF: concat = some any ololo;
new Concatenation(<RULE_ID>, [<some_ID>, <any_ID>, <ololo_ID>], 'concat');
```

### Repetition

Repeat one or more rules.

```php
// EBNF: repeat zero or more = some*
new Repetition(<RULE_ID>, 0, -1, [<some_ID>], 'repeat zero or more');

// EBNF: repeat one or more = some+
new Repetition(<RULE_ID>, 1, -1, [<some_ID>], 'repeat one or more');

// EBNF: repeat = (some any)*
new Repetition(<RULE_ID>, 0, -1, [<some_ID>, <any_ID>], 'repeat');

// EBNF: repeat zero or one = [some]
new Repetition(<RULE_ID>, 0, 1, [<some_ID>, <any_ID>], 'repeat zero or one');
```

### Token

Refers to the token defined in the lexer.

```php
// Lexer: `->add('T_NUMBER', '\\d+')`
new Token(<RULE_ID>, 'T_NUMBER');

// Lexer: `->add('T_WHITESPACE', '\\s+')`
new Token(<RULE_ID>, 'T_WHITESPACE', false);
```

## Examples

A more complex example of a math:

```ebnf
expression = T_NUMBER operation ( T_NUMBER | expression ) ;
operation = T_PLUS | T_MINUS ;
```

```php
$parser = new Parser($lexer, [
    new Concatenation(0, [8, 6, 7], 'expression'),  // expression = T_NUMBER operation ( ... ) ;
    new Alternation(7, [8, 0]),                     // ( T_NUMBER | expression ) ;
    new Alternation(6, [1, 2], 'operation'),        // operation = T_PLUS | T_MINUS ;
    new Token(8, 'T_NUMBER'),
    new Token(1, 'T_PLUS'),
    new Token(2, 'T_MINUS'),
], [Parser::PRAGMA_ROOT => 'expression']);

echo $parser->parse(File::fromSources('2 + 2 - 10 + 1000'));
```

Result:

```xml
<Ast>
  <Rule name="expression" offset="0">
    <Leaf name="T_NUMBER" offset="0">2</Leaf>
    <Rule name="operation" offset="2">
      <Leaf name="T_PLUS" offset="2">+</Leaf>
    </Rule>
    <Rule name="expression" offset="4">
      <Leaf name="T_NUMBER" offset="4">2</Leaf>
      <Rule name="operation" offset="6">
        <Leaf name="T_MINUS" offset="6">-</Leaf>
      </Rule>
      <Rule name="expression" offset="8">
        <Leaf name="T_NUMBER" offset="8">10</Leaf>
        <Rule name="operation" offset="11">
          <Leaf name="T_PLUS" offset="11">+</Leaf>
        </Rule>
        <Leaf name="T_NUMBER" offset="13">1000</Leaf>
      </Rule>
    </Rule>
  </Rule>
</Ast>
```
