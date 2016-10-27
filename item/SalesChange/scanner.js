function enableLinea(selector, callback)
{
    Device = new ScannerDevice({
        barcodeData: function (data, type){
            var upc = data.substring(0,data.length-1);
            if ($(selector).length > 0){
                $(selector).val(upc);
                if (typeof callback === 'function') {
                    callback();
                } else {
                    $(selector).closest('form').submit();
                }
            }
        },
        magneticCardData: function (track1, track2, track3){
        },
        magneticCardRawData: function (data){
        },
        buttonPressed: function (){
        },
        buttonReleased: function (){
        },
        connectionState: function (state){
        }
    });
    ScannerDevice.registerListener(Device);

    function lineaSilent()
    {
        if (typeof cordova.exec != 'function') {
            setTimeout(lineaSilent, 100);
        } else {
            if (Device) {
                Device.setScanBeep(false, []);
            }
        }
    }
    lineaSilent();
}

