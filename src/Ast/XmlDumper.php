<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

/**
 * Class XmlDumper
 */
class XmlDumper implements NodeDumperInterface
{
    private const OUTPUT_CHARSET     = 'UTF-8';
    private const OUTPUT_XML_VERSION = '1.1';

    /**
     * @var int
     */
    protected $initialIndention = 0;

    /**
     * @var int
     */
    protected $indention = 4;

    /**
     * @var NodeInterface
     */
    private $ast;

    /**
     * NodeDumper constructor.
     * @param NodeInterface $ast
     */
    public function __construct(NodeInterface $ast)
    {
        $this->ast = $ast;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $dom = new \DOMDocument(self::OUTPUT_XML_VERSION, self::OUTPUT_CHARSET);

        $dom->formatOutput = true;

        $root = $dom->createElement('Ast');
        $root->appendChild($this->renderAsXml($dom, $this->ast));

        return $dom->saveXML($root);
    }

    /**
     * @param \DOMDocument $root
     * @param NodeInterface|LeafInterface|RuleInterface $ast
     * @return \DOMElement
     */
    private function renderAsXml(\DOMDocument $root, NodeInterface $ast): \DOMElement
    {
        if ($ast instanceof LeafInterface) {
            $token = $root->createElement(\class_basename($ast), $ast->getValue());

            $token->setAttribute('name', $ast->getName());
            $token->setAttribute('offset', (string)$ast->getOffset());

            return $token;
        }

        $node = $root->createElement(\class_basename($ast));
        $node->setAttribute('name', \ltrim($ast->getName(), '#'));
        $node->setAttribute('offset', (string)$ast->getOffset());

        /** @var NodeInterface $child */
        foreach ($ast->getChildren() as $child) {
            $node->appendChild($this->renderAsXml($root, $child));
        }

        return $node;
    }

    /**
     * @return string
     * @deprecated Use toString method instead
     */
    public function toXml(): string
    {
        return $this->toString();
    }

    /**
     * @param string $text
     * @return string
     */
    protected function inline(string $text): string
    {
        return \str_replace(["\n", "\r", "\t"], ['\n', '', '\t'], $text);
    }

    /**
     * @param int $depth
     * @param int $initial
     * @return $this
     */
    protected function setIndention(int $depth = 4, int $initial = 0): self
    {
        $this->indention        = $depth;
        $this->initialIndention = $initial;

        return $this;
    }

    /**
     * @param string $line
     * @param int $depth
     * @return string
     */
    protected function depth(string $line, int $depth = 0): string
    {
        $prefix = \str_repeat(' ', $depth * (
                $this->initialIndention + $this->indention
            ));

        return $prefix . $line . \PHP_EOL;
    }
}
