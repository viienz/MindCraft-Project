<?php
require_once __DIR__ . '/../model/LLMService.php';

class LLMController {
    public function getResponse($prompt) {
        $llm = new LLMService();
        return $llm->askGroq($prompt);
    }
}