⚠️ **Migration from Yii1**  
The `DateValidator` format syntax has changed from Yii1 (`MM/dd/yyyy`) to PHP-native (`m/d/Y`).  
After upgrading, **review and update your `format` strings** to match PHP `DateTime` masks.