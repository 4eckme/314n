                </div>
                <a name="end"></a>

                <form action="console.php" method="POST">
                    <input type="hidden" name="nojs" value="1">
                    <div class="line">
                        <div id="path"><?php echo $path; ?>&gt;&nbsp;</div>
                        <input type="checkbox" name="multiline" value="1" id="input-type-switch" style="display: none">
                        <textarea id="cmd" name="input"><?php echo $edittext; ?></textarea>
                        <input type="text" name="input_i" id="cmd_i" value="<?php echo $edittext; ?>" autofocus="true">
                        <label for="input-type-switch">Multiline</label>
                        <div class="submit-container">
                            <button type="submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
