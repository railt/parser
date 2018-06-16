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
use Railt\Parser\Ast\Delegate;
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Exception\UnrecognizedRuleException;
use Railt\Parser\Exception\UnrecognizedTokenException;
use Railt\Parser\Iterator\Buffer;
use Railt\Parser\Iterator\BufferInterface;
use Railt\Parser\Rule\Production;
use Railt\Parser\Rule\ProvideRules;
use Railt\Parser\Rule\Symbol;
use Railt\Parser\Runtime\RuntimeInterface;

/**
 * Class Parser
 */
class Parser implements ParserInterface, ProvideRules
{
    public const PRAGMA_LOOKAHEAD = 'parser.lookahead';
    public const PRAGMA_ROOT = 'parser.root';
    public const PRAGMA_RUNTIME = 'parser.runtime';

    /**
     * @var array|Symbol[]
     */
    protected $rules = [];

    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var array|string[]
     */
    private $delegates = [];

    /**
     * @var array|string[]
     */
    private $runtime = [
        'llk' => Runtime\LlkRuntime::class,
        'll1' => Runtime\Ll1Runtime::class,
    ];

    /**
     * Parser constructor.
     * @param LexerInterface $lexer
     * @param iterable $rules
     * @param array $config
     */
    public function __construct(LexerInterface $lexer, iterable $rules, array $config = [])
    {
        $this->lexer  = $lexer;
        $this->config = new Configuration($config);
        $this->addRules($rules);
    }

    /**
     * @param string $from
     * @param string|Delegate $to
     * @return ParserInterface
     * @throws \InvalidArgumentException
     */
    public function addDelegate(string $from, string $to): ParserInterface
    {
        if (! \class_exists($to)) {
            throw new \InvalidArgumentException('Delegate should be a valid class name');
        }

        if (! \is_subclass_of($to, Delegate::class)) {
            $error = \sprintf('Delegate should be an instance of %s', Delegate::class);
            throw new \InvalidArgumentException($error);
        }

        $this->delegates[$from] = $to;

        return $this;
    }

    /**
     * @param iterable|string[]|Delegate[] $delegates
     * @return ParserInterface
     * @throws \InvalidArgumentException
     */
    public function addDelegates(iterable $delegates): ParserInterface
    {
        foreach ($delegates as $rule => $delegate) {
            $this->addDelegate($rule, $delegate);
        }

        return $this;
    }

    /**
     * @param Symbol $symbol
     * @return ProvideRules
     */
    public function addRule(Symbol $symbol): ProvideRules
    {
        $this->rules[$symbol->getId()] = $symbol;

        return $this;
    }

    /**
     * @param iterable $symbols
     * @return ProvideRules
     */
    public function addRules(iterable $symbols): ProvideRules
    {
        foreach ($symbols as $symbol) {
            $this->addRule($symbol);
        }

        return $this;
    }

    /**
     * @param Readable $input
     * @return RuleInterface
     * @throws UnrecognizedRuleException
     * @throws \LogicException
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    public function parse(Readable $input): RuleInterface
    {
        $buffer = $this->createBuffer($input);
        $buffer->rewind();

        $trace = $this->createRuntime()->parse($input, $buffer);

        return (new Builder($trace, $this->delegates))->reduce();
    }

    /**
     * @param Readable $input
     * @return BufferInterface
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    protected function createBuffer(Readable $input): BufferInterface
    {
        $lookahead = (int)$this->config->get(static::PRAGMA_LOOKAHEAD, 1024);

        return new Buffer($this->lex($input), $lookahead);
    }

    /**
     * @param Readable $input
     * @return \Traversable|TokenInterface[]
     * @throws \Railt\Io\Exception\ExternalFileException
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
     * @throws UnrecognizedRuleException
     */
    protected function createRuntime(): RuntimeInterface
    {
        $key     = $this->config->get(static::PRAGMA_RUNTIME, \array_keys($this->runtime)[0]);
        $runtime = $this->runtime[$key] ?? \array_values($this->runtime)[0];

        return new $runtime($this, $this->getRootRule());
    }

    /**
     * @return Production|Symbol
     * @throws UnrecognizedRuleException
     */
    protected function getRootRule(): Production
    {
        $name = $this->config->get(static::PRAGMA_ROOT);

        foreach ($this->rules as $rule) {
            if ($rule instanceof Production && $rule->getName()) {
                if ($name === null || $rule->getName() === $name) {
                    return $rule;
                }
            }
        }

        throw new UnrecognizedRuleException('Can not resolve root rule');
    }

    /**
     * @param int|string $id
     * @return Symbol
     * @throws UnrecognizedRuleException
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
