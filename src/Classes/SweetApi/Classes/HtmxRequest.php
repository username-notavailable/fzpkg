<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Classes;

use Illuminate\Http\Request;

class HtmxRequest extends Request
{
    public function isBoosted() : bool
    {
        return $this->headers->has('HX-Boosted');
    }

    public function isForHistoryRestoration() : ?bool
    {
        if ($this->headers->has('HX-History-Restore-Request')) {
            return $this->headers->get('HX-History-Restore-Request') === "true";
        }
        else {
            return null;
        }
    } 

    public function getBrowserCurrentUrl() : ?string
    {
        return $this->headers->has('HX-Current-URL') ? $this->headers->get('HX-Current-URL') : null;
    }

    public function getUserPromptResponse() : ?string
    {
        return $this->headers->has('HX-Prompt') ? $this->headers->get('HX-Prompt') : null;
    }

    public function getTargetId() : ?string
    {
        return $this->headers->has('HX-Target') ? $this->headers->get('HX-Target') : null;
    }

    public function getTriggerId() : ?string
    {
        return $this->headers->has('HX-Trigger') ? $this->headers->get('HX-Trigger') : null;
    }

    public function getTriggerName() : ?string
    {
        return $this->headers->has('HX-Trigger-Name') ? $this->headers->get('HX-Trigger-Name') : null;
    }
}