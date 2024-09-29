<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Classes;

use Illuminate\Http\Response;

class HtmxResponse extends Response
{
    public function setHxLocation(string $location) : self
    {
        $this->headers->set('HX-Location', $location);
        return $this;
    }

    public function setHxPushUrl(string $url) : self
    {
        $this->headers->set('HX-Push-Url', $url);
        return $this;
    }

    public function setHxRedirect(string $url) : self
    {
        $this->headers->set('HX-Redirect', $url);
        return $this;
    }

    public function setHxRefresh(bool $value = true) : self
    {
        $this->headers->set('HX-Refresh', $value ? 'true' : 'false');
        return $this;
    }

    public function setHxReplaceUrl(string $url) : self
    {
        $this->headers->set('HX-Replace-Url', $url);
        return $this;
    }

    public function setHxReswap(string $value) : self
    {
        $this->headers->set('HX-Reswap', $value);
        return $this;
    }

    public function setHxRetarget(string $value) : self
    {
        $this->headers->set('HX-Retarget', $value);
        return $this;
    }

    public function setHxReselect(string $value) : self
    {
        $this->headers->set('HX-Reselect', $value);
        return $this;
    }

    public function setHxTrigger(string $value) : self
    {
        $this->headers->set('HX-Trigger', $value);
        return $this;
    }

    public function setHxTriggerAfterSettle(string $value) : self
    {
        $this->headers->set('HX-Trigger-After-Settle', $value);
        return $this;
    }

    public function setHxTriggerAfterSwap(string $value) : self
    {
        $this->headers->set('HX-Trigger-After-Swap', $value);
        return $this;
    }
}