<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>
        <?php echo $MSG_REG_INFO; ?> - <?php echo $OJ_NAME; ?>
    </title>
    <?php require("./template/bshark/header-files.php"); ?>
</head>

<body>
    <?php require("./template/bshark/nav.php"); ?>
    <?php require_once('./include/set_post_key.php'); ?>
    <div class="ui container bsharkMain">
        <div class="card">
            <div class="card-body">
                <div class="padding">
                    <h1>
                        <?php echo $MSG_REG_INFO; ?>
                    </h1>
                    <div class="ui error message" id="error" data-am-alert hidden>
                        <p id="error_info"></p>
                    </div>
                    <form action="modify.php" method="post" role="form" class="ui form">
                        <div class="field">
                            <label for="username">
                                <?php echo $MSG_USER_ID; ?>
                            </label>
                            <input class="form-control"
                                placeholder="请<?php echo $MSG_Input; ?><?php echo $MSG_USER_ID; ?>" disabled="disabled"
                                type="text" value="<?php echo $_SESSION[$OJ_NAME . '_' . 'user_id'] ?>">
                        </div>
                        <?php require_once('./include/set_post_key.php'); ?>
                        <div class="field">
                            <label for="username">
                                <span style="color:#db2828">*</span>
                                <?php echo $MSG_NICK; ?>
                            </label>
                            <input name="nick" placeholder="请<?php echo $MSG_Input; ?><?php echo $MSG_NICK; ?>"
                                type="text" value="<?php echo htmlentities($row['nick'], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="field">
                            <label class="ui header">
                                <span style="color:#db2828">*</span>
                                <?php echo $MSG_PASSWORD; ?>
                            </label>
                            <input name="opassword" placeholder="请<?php echo $MSG_Input; ?><?php echo $MSG_PASSWORD; ?>"
                                type="password">
                        </div>
                        <div class="two fields">
                            <div class="field">
                                <label class="ui header">
                                    新<?php echo $MSG_PASSWORD; ?>
                                </label>
                                <input name="npassword" placeholder="无需<?php echo $MSG_SETPASSWORD; ?>，请勿填写此项"
                                    type="password">
                            </div>
                            <div class="field">
                                <label class="ui header">
                                    <?php echo $MSG_REPEAT_PASSWORD; ?>
                                </label>
                                <input name="rptpassword" placeholder="无需<?php echo $MSG_SETPASSWORD; ?>，请勿填写此项"
                                    type="password">
                            </div>
                        </div>
                        <div class="field">
                            <label for="username">个性签名</label>
                            <input name="school" placeholder="请<?php echo $MSG_Input; ?>个性签名" type="text"
                                value="<?php echo htmlentities($row['school'], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="field">
                            <label for="email">
                                <span style="color:#db2828">*</span>
                                <?php echo $MSG_EMAIL; ?>
                            </label>
                            <input name="email" placeholder="请<?php echo $MSG_Input; ?><?php echo $MSG_EMAIL; ?>"
                                type="text" value="<?php echo htmlentities($row['email'], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <?php if ($OJ_VCODE) { ?>
                            <div class="field">
                                <label for="email">
                                    <span style="color:#db2828">*</span>
                                    <?php echo $MSG_VCODE; ?>
                                </label>
                                <input name="vcode" class="form-control"
                                    placeholder="请<?php echo $MSG_Input; ?><?php echo $MSG_VCODE; ?>" type="text">
                                <img alt="click to change" src="vcode.php" onclick="this.src='vcode.php?'+Math.random()"
                                    height="30px">
                            </div>
                        <?php } ?>
                        <button name="submit" type="submit" class="ui primary button"><?php echo $MSG_SUBMIT; ?></button>
                        <button name="submit" type="reset" class="ui button">
                            <?php echo $MSG_RESET; ?>
                        </button>
                    </form>
                </div>
                <?php if ($OJ_SaaS_ENABLE && $domain == $DOMAIN) { ?>
                    <div class="center"> <a name='MyOJ'>&nbsp;</a> <label>My OJ:</label>
                        <form action="saasinit.php" method="post" role="form" class="ui form">
                            <div class="field">
                                <label for="template">模板</label>
                                <select name="template" class="form-control">
                                    <option>bs3</option>
                                    <option>mdui</option>
                                    <option>syzoj</option>
                                    <option>sweet</option>
                                    <option>bshark</option>
                                    <option>mario</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="friendly">友善级别</label>
                                <select name="friendly" class="form-control">
                                    <option value=0>0=不友善</option>
                                    <option value=1>1=0+中国时区</option>
                                    <option value=2>2=1+强制中文</option>
                                    <option value=3>3=2+显示对比,关闭验证码</option>
                                    <option value=4>4=3+开启内邮,代码自动分享</option>
                                    <option value=5>5=4+开启测试运行</option>
                                    <option value=6>6=5+保持登陆状态</option>
                                    <option value=7>7=6+开启讨论版</option>
                                    <option value=8>8=7+可以下载测试数据</option>
                                    <option value=9>9=8+允许访客提交</option>
                                </select>
                            </div>
                            <button name="submit" type="submit" class="ui button">重新初始化</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php require("./template/bshark/footer.php"); ?>
    <?php require("./template/bshark/footer-files.php"); ?>
</body>

</html>