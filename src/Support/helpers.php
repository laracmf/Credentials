<?php

function isAdmin()
{
    return \GrahamCampbell\Credentials\Facades\Credentials::inRole('admin');
}