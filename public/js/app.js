/**
* Add jQuery Validation plugin method for a valid password
*
* Valid passwords contain at least one letter and one number
*/

$.validator.addMethod('validatePassword',
      function(value, element, param)
      {
        if (value != '')
        {
          if (value.match(/.*[a-z]+.*/i) == null) // At least one letter
          {
            return false;
          }
          if (value.match(/.*\d+.*/) == null) // At least one number
          {
            return false;
          }
        }
        return true;
      },
      'Must contain at least one number and one letter'
    );
