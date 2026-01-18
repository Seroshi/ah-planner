<?php

use App\Models\Visitor;

if (! function_exists('visitor')) {
   /**
   * Get the currently active visitor for the session.
   */
   function visitor()
   {

      // Get the ID from the session
      $visitorId = session('active_visitor_id');

      if(!$visitorId) return null;
   
      static $cachedVisitor;

      // If not empty, find Id in the database
      return $cachedVisitor ??= Visitor::find($visitorId);

    }
}