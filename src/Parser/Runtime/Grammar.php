<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

/**
 * Class Grammar
 */
class Grammar implements GrammarInterface
{
    /**
     * List of "virtual" rules created by the analyzer
     * and not declared in the grammar.
     *
     * @var array|int[]
     */
    public $transitional = [];

    /**
     * The parser starts with this initial rule identifier.
     *
     * @var int
     */
    public $root;

    /**
     * List of actions depending on the state.
     *
     * - 0 means choosing one of the valid rules for
     *      the subsequent transition (Alternation).
     *
     * - 1 means a sequence of rules (Concatenation).
     *
     * - 2 means repeat the rules several times (Repetition).
     *
     * - 3 means means terminal state (Lexeme).
     *
     * @var array|int[]
     */
    public $actions = [];

    /**
     * The list of names for rules and tokens that will
     * be displayed in the resulting AST.
     *
     * @var array|string[]|null[]
     */
    public $names = [];

    /**
     * The jumps list.
     *
     * - For each type 0 and 1 (defined in the list of actions)
     *      contains a list of rules included in the sequence.
     *
     * - For type 2 (repetition) contains information about the
     *      repeated rule ID, the minimum and maximum number
     *      of repetitions.
     *
     * - For type 3 (terminal) contains an indication of whether
     *      or not to save the terminal as a separate Leaf of AST.
     *
     * @var array|bool[]|array[]
     */
    public $goto = [];
}
