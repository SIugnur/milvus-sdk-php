<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\RunAnalyzerResponse as ProtoRunAnalyzerResponse;

class RunAnalyzerResult
{
    private ProtoRunAnalyzerResponse $raw;

    public function __construct(ProtoRunAnalyzerResponse $raw)
    {
        $this->raw = $raw;
    }

    /**
     * Get the raw protobuf response.
     */
    public function getRaw(): ProtoRunAnalyzerResponse
    {
        return $this->raw;
    }

    /**
     * Get analyzer results as an array of token arrays.
     *
     * Each result entry contains tokens with keys: token, start_offset, end_offset, position, position_length, hash.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getResults(): array
    {
        $results = $this->raw->getResults();
        if ($results === null) {
            return [];
        }

        $output = [];
        foreach ($results as $result) {
            $tokens = $result->getTokens();
            $tokenList = [];
            if ($tokens !== null) {
                foreach ($tokens as $token) {
                    $tokenList[] = [
                        'token' => $token->getToken(),
                        'start_offset' => $token->getStartOffset(),
                        'end_offset' => $token->getEndOffset(),
                        'position' => $token->getPosition(),
                        'position_length' => $token->getPositionLength(),
                        'hash' => $token->getHash(),
                    ];
                }
            }
            $output[] = $tokenList;
        }

        return $output;
    }

    /**
     * Get the status from the response.
     */
    public function getStatus()
    {
        return $this->raw->getStatus();
    }
}
