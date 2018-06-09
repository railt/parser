<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

use Railt\Io\Readable;
use Railt\Lexer\LexerInterface;
use Railt\Lexer\Result\Unknown;
use Railt\Lexer\TokenInterface;
use Railt\Parser\Ast\Builder;
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Exception\UnrecognizedRuleException;
use Railt\Parser\Exception\UnrecognizedTokenException;
use Railt\Parser\Iterator\Buffer;
use Railt\Parser\Iterator\BufferInterface;
use Railt\Parser\Rule\Production;
use Railt\Parser\Rule\RulesContainerInterface;
use Railt\Parser\Rule\Symbol;
use Railt\Parser\Runtime\LlkRuntime;
use Railt\Parser\Runtime\RuntimeInterface;

/**
 * Class Parser
 */
class Parser implements ParserInterface, RulesContainerInterface
{
    /**
     * @var int
     */
    protected $lookahead = 1024;

    /**
     * @var array|Symbol[]
     */
    protected $rules = [];

    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * @var int|null
     */
    private $root;

    /**
     * Parser constructor.
     * @param LexerInterface $lexer
     * @param iterable $rules
     */
    public function __construct(LexerInterface $lexer, iterable $rules = [])
    {
        $this->lexer = $lexer;

        foreach ($rules as $rule) {
            $this->add($rule);
        }
    }

    /**
     * @param Symbol $symbol
     * @return RulesContainerInterface
     */
    public function add(Symbol $symbol): RulesContainerInterface
    {
        $this->rules[$symbol->getId()] = $symbol;

        return $this;
    }

    /**
     * @param Readable $input
     * @return RuleInterface
     */
    public function parse(Readable $input): RuleInterface
    {
        $buffer = $this->createBuffer($input);
        $buffer->rewind();

        $trace = $this->createRuntime()->parse($input, $buffer);

        \var_dump($trace);

        return (new Builder($this, $trace))->reduce();
    }

    /**
     * @param Readable $input
     * @return BufferInterface
     */
    protected function createBuffer(Readable $input): BufferInterface
    {
        return new Buffer($this->lex($input), $this->lookahead);
    }

    /**
     * @param Readable $input
     * @return \Traversable|TokenInterface[]
     */
    protected function lex(Readable $input): \Traversable
    {
        $tokens = $this->lexer->lex($input, true);

        foreach ($tokens as $token) {
            if ($token->name() === Unknown::T_NAME) {
                $error = \sprintf('Unrecognized token "%s" (%s)', $token->value(), $token->name());
                throw (new UnrecognizedTokenException($error))->throwsIn($input, $token->offset());
            }

            yield $token;
        }
    }

    /**
     * @return RuntimeInterface
     */
    protected function createRuntime(): RuntimeInterface
    {
        return new LlkRuntime($this, $this->getRootRule());
    }

    /**
     * @return Production|Symbol
     */
    protected function getRootRule(): Production
    {
        if ($this->root === null) {
            foreach ($this->rules as $rule) {
                if ($rule instanceof Production && $rule->getName()) {
                    return $rule;
                }
            }

            throw new UnrecognizedRuleException('Can not resolve root rule');
        }

        return $this->fetch($this->root);
    }

    /**
     * @param int|string $id
     * @return Symbol
     */
    public function fetch($id): Symbol
    {
        if (\array_key_exists($id, $this->rules)) {
            return $this->rules[$id];
        }

        $error = \sprintf('Can not restore a rule with an identifier "%d"', $id);
        throw new UnrecognizedRuleException($error);
    }
}
