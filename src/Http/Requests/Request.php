<?php

namespace GrahamCampbell\BootstrapCMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

abstract class Request extends FormRequest
{
    /**
     * Get the proper failed validation response for the request.
     *
     * @param  array $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response(array $errors)
    {
        if (($this->ajax() && !$this->pjax()) || $this->wantsJson()) {
            $output = [];
            foreach ($errors as $key => $error) {
                $output[] = ['parameter' => $key, 'title' => $error[0], 'code' => 0];
            }
            return new JsonResponse(['errors' => $output], 401);
        }

        return $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))
            ->withErrors($errors, $this->errorBag);
    }
}