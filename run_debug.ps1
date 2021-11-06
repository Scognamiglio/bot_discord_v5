while ($TRUE){
    php heart.php | Tee-Object -Variable cmdOutput
    if($cmdOutput[$cmdOutput.length-1] -notlike "newRun"){
        break
    }
}