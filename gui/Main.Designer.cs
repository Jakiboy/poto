namespace Poto
{
    partial class Main
    {
        /// <summary>
        ///  Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        ///  Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        ///  Required method for Designer support - do not modify
        ///  the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            upload = new Button();
            icon = new PictureBox();
            author = new LinkLabel();
            about = new Label();
            ((System.ComponentModel.ISupportInitialize)icon).BeginInit();
            SuspendLayout();
            // 
            // upload
            // 
            upload.Location = new Point(111, 131);
            upload.Name = "upload";
            upload.Size = new Size(108, 23);
            upload.TabIndex = 0;
            upload.Text = "Upload .po";
            upload.UseVisualStyleBackColor = true;
            upload.Click += UploadClick;
            // 
            // icon
            // 
            icon.ErrorImage = null;
            icon.Image = Properties.Resources.icon;
            icon.Location = new Point(111, 19);
            icon.Name = "icon";
            icon.Size = new Size(108, 106);
            icon.TabIndex = 8;
            icon.TabStop = false;
            // 
            // author
            // 
            author.AutoSize = true;
            author.LinkBehavior = LinkBehavior.AlwaysUnderline;
            author.LinkColor = Color.MediumSlateBlue;
            author.Location = new Point(8, 169);
            author.Name = "author";
            author.Size = new Size(46, 15);
            author.TabIndex = 7;
            author.TabStop = true;
            author.Text = "Jakiboy";
            author.LinkClicked += AuthorClicked;
            // 
            // about
            // 
            about.AutoSize = true;
            about.Enabled = false;
            about.Location = new Point(52, 170);
            about.Name = "about";
            about.Size = new Size(43, 15);
            about.TabIndex = 8;
            about.Text = "| v0.1.0";
            // 
            // Main
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(334, 193);
            Controls.Add(icon);
            Controls.Add(about);
            Controls.Add(author);
            Controls.Add(upload);
            FormBorderStyle = FormBorderStyle.FixedDialog;
            MaximizeBox = false;
            MdiChildrenMinimizedAnchorBottom = false;
            Name = "Main";
            Text = "Poto [.PO translator]";
            TopMost = true;
            FormClosed += FormClose;
            Load += FormLoad;
            ((System.ComponentModel.ISupportInitialize)icon).EndInit();
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private Button upload;
        private PictureBox icon;
        private LinkLabel author;
        private Label about;
    }
}