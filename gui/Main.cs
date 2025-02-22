using System.Diagnostics;

namespace Poto
{
    public partial class Main : Form
    {
        private readonly string appFolder;
        private readonly string guid;

        public Main()
        {
            InitializeComponent();
            this.StartPosition = FormStartPosition.CenterScreen;
            this.appFolder = AppDomain.CurrentDomain.BaseDirectory;
            this.guid = Guid.NewGuid().ToString() + "-";
        }

        private void FormLoad(object sender, EventArgs e) { }

        private void FormClose(object sender, FormClosedEventArgs e) { }

        private void UploadClick(object sender, EventArgs e)
        {
            OpenFileDialog openFileDialog = new OpenFileDialog
            {
                Filter = "Binary files (*.po)|*.po"
            };

            if (openFileDialog.ShowDialog() == DialogResult.OK)
            {
                try
                {
                    // Disable upload button and update text
                    this.upload.Enabled = false;
                    this.upload.Text = "Translating...";

                    // Upload
                    string filePath = openFileDialog.FileName;
                    string fileName = guid + Path.GetFileName(filePath);
                    string poFile = Path.Combine(appFolder, fileName);

                    // Translate init
                    string translator = Path.Combine(appFolder, "translator.exe");
                    translator = Path.GetFullPath(translator);

                    // Check if the translator executable exists
                    if (!File.Exists(translator))
                    {
                        MessageBox.Show("Translator is missing.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        return;
                    }

                    string args = $"/c {translator} {poFile}";

                    // Decode process
                    Process process = new Process
                    {
                        StartInfo = new ProcessStartInfo
                        {
                            FileName = "cmd.exe",
                            Arguments = args,
                            UseShellExecute = false,
                            RedirectStandardOutput = true,
                            CreateNoWindow = true
                        }
                    };

                    process.Start();
                    process.WaitForExit();

                    // Check if the decoding process succeeded
                    if (process.ExitCode != 0)
                    {
                        MessageBox.Show("Failed to decode the .po file", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        return;
                    }

                }
                catch (Exception ex)
                {
                    // Handle any unexpected errors
                    MessageBox.Show($"An error occurred: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
                finally
                {
                    // Re-enable the upload button and reset its text
                    this.upload.Enabled = true;
                    this.upload.Text = "Upload .po";
                }
            }
        }

        private void AuthorClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            var url = new ProcessStartInfo("https://github.com/Jakiboy")
            {
                UseShellExecute = true,
                Verb = "open"
            };
            Process.Start(url);
        }

        private void HowClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            var url = new ProcessStartInfo("https://github.com/Jakiboy/Poto/blob/main/HOW.md")
            {
                UseShellExecute = true,
                Verb = "open"
            };
            Process.Start(url);
        }

    }
}