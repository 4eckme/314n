                </div>
                <a name="end"></a>

                <form action="console.php" method="POST">
                    <input type="hidden" name="nojs" value="1">
                    
                    <table class="line"><tr>
                        <td id="path"><?php echo $path; ?>&gt;&nbsp;</td>
                        <td class="input-container">
                            <input type="checkbox" name="multiline" value="1" id="input-type-switch" style="display: none">
                            <textarea id="cmd" name="input"><?php echo $edittext; ?></textarea>
                            <input type="text" name="input_i" id="cmd_i" value="<?php echo $edittext; ?>" autofocus="true">
                            <div class="submit-container">
                                <label for="input-type-switch">Multiline</label>
                                <button type="submit">Submit</button>
                            </div>
                        </td>    
                    </tr></table>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
