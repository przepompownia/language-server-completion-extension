<?php

namespace Phpactor\Extension\WorseLanguageServer\Handler;

use Generator;
use LanguageServerProtocol\Location;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\TextDocumentIdentifier;
use Phpactor\Extension\LanguageServer\Helper\OffsetHelper;
use Phpactor\LanguageServer\Core\Dispatcher\Handler;
use Phpactor\LanguageServer\Core\Session\SessionManager;
use Phpactor\ReferenceFinder\DefinitionLocator;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;

class GotoDefinitionHandler implements Handler
{
    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * @var DefinitionLocator
     */
    private $definitionLocator;

    public function __construct(SessionManager $sessionManager, DefinitionLocator $definitionLocator)
    {
        $this->sessionManager = $sessionManager;
        $this->definitionLocator = $definitionLocator;
    }

    public function methods(): array
    {
        return [
            'textDocument/definition' => 'definition',
        ];
    }

    public function definition(
        TextDocumentIdentifier $textDocument,
        Position $position
    ): Generator {

        $textDocument = $this->sessionManager->current()->workspace()->get($textDocument->uri);

        $offset = $position->toOffset($textDocument->text);

        $location = $this->definitionLocator->locateDefinition(
            TextDocumentBuilder::create($textDocument->text)->uri($textDocument->uri)->language('php')->build(),
            ByteOffset::fromInt($offset)
        );


        // this _should_ exist for sure, but would be better to refactor the
        // goto definition result to return the source code.
        $sourceCode = file_get_contents($location->uri());

        $startPosition = OffsetHelper::offsetToPosition(
            $sourceCode,
            $location->offset()->toInt()
        );

        $location = new Location('file://' . $location->uri(), new Range(
            $startPosition,
            $startPosition
        ));

        yield $location;
    }
}