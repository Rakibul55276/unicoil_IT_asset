using System;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Net;
using System.Reflection;
using System.Security.AccessControl;
using System.Security.Principal;
using System.Text;
using System.Text.RegularExpressions;
using System.Web.Script.Serialization;
using System.Windows.Forms;

[assembly: AssemblyTitle("UNICOIL Asset Agent Setup")]
[assembly: AssemblyDescription("Installs the UNICOIL Windows hardware and software inventory agent")]
[assembly: AssemblyCompany("UNICOIL")]
[assembly: AssemblyProduct("UNICOIL Asset Agent")]
[assembly: AssemblyVersion("1.0.3.0")]
[assembly: AssemblyFileVersion("1.0.3.0")]

namespace Unicoil.AssetAgent.Setup
{
    // Entry point for setup mode and the installed local status window. Server
    // code lives separately in the web application's agent-module directory.
    internal static class Program
    {
        [STAThread]
        private static void Main(string[] args)
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            if (args != null && Array.Exists(args, value => string.Equals(value, "--status", StringComparison.OrdinalIgnoreCase)))
                Application.Run(new AgentStatusForm());
            else
                Application.Run(new SetupForm());
        }
    }

    internal sealed class AgentStatusForm : Form
    {
        // End-user view: intentionally limited to Sync Now, Refresh, and
        // Uninstall. Inventory administration remains in the web application.
        private const string TaskName = "UNICOIL Asset Inventory Agent";
        private readonly string installPath = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.CommonApplicationData), "UNICOIL", "AssetAgent");
        private readonly Label status = new Label();
        private readonly Label details = new Label();
        private readonly TextBox log = new TextBox();

        internal AgentStatusForm()
        {
            Text = "UNICOIL Asset Agent";
            StartPosition = FormStartPosition.CenterScreen;
            ClientSize = new Size(650, 430);
            MinimumSize = new Size(666, 469);
            Font = new Font("Segoe UI", 9F);

            Controls.Add(new Label { Text = "UNICOIL Asset Agent", Font = new Font("Segoe UI Semibold", 18F), AutoSize = true, Location = new Point(24, 18), ForeColor = Color.FromArgb(8, 127, 91) });
            Controls.Add(new Label { Text = "Local PC inventory synchronization", AutoSize = true, ForeColor = Color.DimGray, Location = new Point(28, 58) });

            status.AutoSize = true;
            status.Font = new Font("Segoe UI Semibold", 11F);
            status.Location = new Point(28, 92);
            Controls.Add(status);

            details.AutoSize = false;
            details.Location = new Point(28, 124);
            details.Size = new Size(590, 54);
            Controls.Add(details);

            var sync = new Button { Text = "Sync Now", Location = new Point(28, 187), Size = new Size(110, 34), BackColor = Color.FromArgb(8, 127, 91), ForeColor = Color.White, FlatStyle = FlatStyle.Flat };
            sync.Click += delegate { SyncNow(); };
            Controls.Add(sync);
            var refresh = new Button { Text = "Refresh", Location = new Point(148, 187), Size = new Size(95, 34) };
            refresh.Click += delegate { RefreshStatus(); };
            Controls.Add(refresh);
            var uninstall = new Button { Text = "Uninstall", Location = new Point(253, 187), Size = new Size(105, 34), ForeColor = Color.Firebrick };
            uninstall.Click += delegate { UninstallAgent(); };
            Controls.Add(uninstall);

            log.Location = new Point(28, 238);
            log.Size = new Size(590, 165);
            log.Multiline = true;
            log.ReadOnly = true;
            log.ScrollBars = ScrollBars.Vertical;
            log.BackColor = Color.White;
            Controls.Add(log);
            RefreshStatus();
        }

        private void RefreshStatus()
        {
            bool installed = File.Exists(Path.Combine(installPath, "UnicoilAssetAgent.ps1")) && QueryTask();
            status.Text = installed ? "Status: Installed and running" : "Status: Agent installation not found";
            status.ForeColor = installed ? Color.FromArgb(8, 127, 91) : Color.Firebrick;
            details.Text = "Scheduled task: " + TaskName + Environment.NewLine + "Installed location: " + installPath;
            string logPath = Path.Combine(installPath, "agent.log");
            try
            {
                if (!File.Exists(logPath)) log.Text = "No synchronization log has been created yet.";
                else
                {
                    string[] lines = File.ReadAllLines(logPath);
                    int start = Math.Max(0, lines.Length - 30);
                    log.Text = string.Join(Environment.NewLine, lines, start, lines.Length - start);
                    log.SelectionStart = log.TextLength;
                    log.ScrollToCaret();
                }
            }
            catch (Exception ex) { log.Text = "Unable to read the Agent log: " + ex.Message; }
        }

        private bool QueryTask()
        {
            try
            {
                using (Process process = Process.Start(new ProcessStartInfo {
                    FileName = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.System), "schtasks.exe"),
                    Arguments = "/Query /TN " + Quote(TaskName), UseShellExecute = false, CreateNoWindow = true
                })) { process.WaitForExit(); return process.ExitCode == 0; }
            }
            catch { return false; }
        }

        private void SyncNow()
        {
            try
            {
                using (Process process = Process.Start(new ProcessStartInfo {
                    FileName = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.System), "schtasks.exe"),
                    Arguments = "/Run /TN " + Quote(TaskName), UseShellExecute = false, CreateNoWindow = true
                })) { process.WaitForExit(); if (process.ExitCode != 0) throw new InvalidOperationException("The scheduled task could not be started."); }
                MessageBox.Show(this, "Synchronization started successfully.", "UNICOIL Asset Agent", MessageBoxButtons.OK, MessageBoxIcon.Information);
                RefreshStatus();
            }
            catch (Exception ex) { MessageBox.Show(this, ex.Message, "Sync failed", MessageBoxButtons.OK, MessageBoxIcon.Error); }
        }

        private void UninstallAgent()
        {
            if (MessageBox.Show(this, "Remove the UNICOIL Asset Agent from this PC?", "Confirm uninstall",
                MessageBoxButtons.YesNo, MessageBoxIcon.Warning) != DialogResult.Yes) return;
            try
            {
                string folder = Path.Combine(Path.GetTempPath(), "UNICOIL-Agent-Uninstall-" + Guid.NewGuid().ToString("N"));
                Directory.CreateDirectory(folder);
                string script = Path.Combine(folder, "Uninstall-UnicoilAssetAgent.ps1");
                using (Stream input = Assembly.GetExecutingAssembly().GetManifestResourceStream("Uninstall-UnicoilAssetAgent.ps1"))
                {
                    if (input == null) throw new InvalidOperationException("The embedded uninstaller is missing.");
                    using (var output = File.Create(script)) input.CopyTo(output);
                }
                Process.Start(new ProcessStartInfo {
                    FileName = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.System), "WindowsPowerShell\\v1.0\\powershell.exe"),
                    Arguments = "-NoProfile -NonInteractive -ExecutionPolicy Bypass -File " + Quote(script) + " -WaitForProcessId " + Process.GetCurrentProcess().Id,
                    UseShellExecute = false,
                    CreateNoWindow = true
                });
                Application.Exit();
            }
            catch (Exception ex) { MessageBox.Show(this, ex.Message, "Uninstall failed", MessageBoxButtons.OK, MessageBoxIcon.Error); }
        }

        private static string Quote(string value) { return "\"" + value.Replace("\"", "\\\"") + "\""; }
    }

    internal sealed class SetupForm : Form
    {
        // First-run installer. Sensitive values are passed to PowerShell using
        // a temporary ACL-protected file that is removed after installation.
        private readonly TextBox serverUrl = new TextBox();
        private readonly TextBox apiToken = new TextBox();
        private readonly TextBox assetNumber = new TextBox();
        private readonly NumericUpDown interval = new NumericUpDown();
        private readonly TextBox output = new TextBox();
        private readonly Button testButton = new Button();
        private readonly Button installButton = new Button();
        private readonly Button uninstallButton = new Button();

        internal SetupForm()
        {
            Text = "UNICOIL Asset Agent Setup";
            StartPosition = FormStartPosition.CenterScreen;
            ClientSize = new Size(690, 445);
            MinimumSize = new Size(706, 484);
            Font = new Font("Segoe UI", 9F);
            MaximizeBox = false;

            var title = new Label {
                Text = "UNICOIL Windows Asset Agent",
                Font = new Font("Segoe UI Semibold", 18F),
                AutoSize = true,
                Location = new Point(24, 18)
            };
            var subtitle = new Label {
                Text = "Synchronize PC hardware and installed software with IT Asset Management.",
                AutoSize = true,
                ForeColor = Color.DimGray,
                Location = new Point(28, 58)
            };
            Controls.Add(title);
            Controls.Add(subtitle);

            AddLabel("Server sync URL", 28, 95);
            serverUrl.Location = new Point(170, 91);
            serverUrl.Size = new Size(485, 24);
            serverUrl.Text = "https://unicoil.online/agent-api/sync.php";
            Controls.Add(serverUrl);

            AddLabel("API token", 28, 133);
            apiToken.Location = new Point(170, 129);
            apiToken.Size = new Size(485, 24);
            apiToken.UseSystemPasswordChar = true;
            Controls.Add(apiToken);

            AddLabel("Asset number", 28, 171);
            assetNumber.Location = new Point(170, 167);
            assetNumber.Size = new Size(210, 24);
            assetNumber.CharacterCasing = CharacterCasing.Upper;
            Controls.Add(assetNumber);

            AddLabel("Sync interval", 400, 171);
            interval.Location = new Point(505, 167);
            interval.Size = new Size(90, 24);
            interval.Minimum = 15;
            interval.Maximum = 1440;
            interval.Value = 360;
            interval.Increment = 15;
            Controls.Add(interval);
            var minutes = new Label { Text = "minutes", AutoSize = true, Location = new Point(600, 171) };
            Controls.Add(minutes);

            testButton.Text = "Test Server";
            testButton.Location = new Point(170, 207);
            testButton.Size = new Size(110, 34);
            testButton.Click += delegate { TestServer(); };
            Controls.Add(testButton);

            installButton.Text = "Install Agent";
            installButton.Location = new Point(290, 207);
            installButton.Size = new Size(120, 34);
            installButton.BackColor = Color.FromArgb(0, 120, 215);
            installButton.ForeColor = Color.White;
            installButton.FlatStyle = FlatStyle.Flat;
            installButton.Click += delegate { InstallAgent(); };
            Controls.Add(installButton);

            uninstallButton.Text = "Uninstall";
            uninstallButton.Location = new Point(420, 207);
            uninstallButton.Size = new Size(100, 34);
            uninstallButton.Click += delegate { UninstallAgent(); };
            Controls.Add(uninstallButton);

            output.Location = new Point(28, 260);
            output.Size = new Size(627, 155);
            output.Multiline = true;
            output.ReadOnly = true;
            output.ScrollBars = ScrollBars.Vertical;
            output.BackColor = Color.White;
            Controls.Add(output);
        }

        private void AddLabel(string text, int x, int y)
        {
            Controls.Add(new Label { Text = text, AutoSize = true, Location = new Point(x, y) });
        }

        private void TestServer()
        {
            try
            {
                Uri syncUri = ValidateServerUrl();
                string health = syncUri.AbsoluteUri.TrimEnd('/');
                if (health.EndsWith("/sync", StringComparison.OrdinalIgnoreCase))
                    health = health.Substring(0, health.Length - 5) + "/health";
                ServicePointManager.SecurityProtocol = SecurityProtocolType.Tls12;
                using (var client = new WebClient())
                {
                    client.Headers[HttpRequestHeader.Accept] = "application/json";
                    string response = client.DownloadString(health);
                    Log("Server test succeeded: " + response);
                }
            }
            catch (Exception ex)
            {
                Log("Server test failed: " + ex.Message);
            }
        }

        private void InstallAgent()
        {
            try
            {
                Uri syncUri = ValidateServerUrl();
                string token = Regex.Replace(apiToken.Text.Trim(), @"\s+", "");
                if (!Regex.IsMatch(token, "^uia_[0-9a-fA-F]{64}$"))
                    throw new InvalidOperationException("The API token must be exactly 68 characters: uia_ followed by 64 hexadecimal characters. Current length: " + token.Length + ".");
                string asset = assetNumber.Text.Trim();
                if (!Regex.IsMatch(asset, "^[A-Za-z0-9_-]{0,30}$"))
                    throw new InvalidOperationException("Asset number may contain only letters, numbers, hyphens, and underscores.");

                SetBusy(true);
                string folder = CreateSecureTemporaryFolder();
                try
                {
                    ExtractResource("UnicoilAssetAgent.ps1", folder);
                    ExtractResource("Install-UnicoilAssetAgent.ps1", folder);
                    var setupConfig = new {
                        serverUrl = syncUri.AbsoluteUri.TrimEnd('/'),
                        apiToken = token,
                        assetNumber = asset,
                        intervalMinutes = (int)interval.Value,
                        setupExecutablePath = Application.ExecutablePath
                    };
                    string configPath = Path.Combine(folder, "setup-config.json");
                    File.WriteAllText(configPath, new JavaScriptSerializer().Serialize(setupConfig), new UTF8Encoding(false));
                    RunPowerShell(Path.Combine(folder, "Install-UnicoilAssetAgent.ps1"),
                        "-SetupConfigPath " + Quote(configPath));
                    Log("Installation completed. The first synchronization has been started.");
                }
                finally
                {
                    TryDeleteFolder(folder);
                    apiToken.Clear();
                    SetBusy(false);
                }
            }
            catch (Exception ex)
            {
                SetBusy(false);
                Log("Installation failed: " + ex.Message);
            }
        }

        private void UninstallAgent()
        {
            try
            {
                if (MessageBox.Show(this, "Remove the UNICOIL Asset Agent from this PC?", "Confirm uninstall",
                    MessageBoxButtons.YesNo, MessageBoxIcon.Question) != DialogResult.Yes) return;
                SetBusy(true);
                string folder = CreateSecureTemporaryFolder();
                try
                {
                    ExtractResource("Uninstall-UnicoilAssetAgent.ps1", folder);
                    RunPowerShell(Path.Combine(folder, "Uninstall-UnicoilAssetAgent.ps1"), "");
                    Log("Agent removed successfully.");
                }
                finally
                {
                    TryDeleteFolder(folder);
                    SetBusy(false);
                }
            }
            catch (Exception ex)
            {
                SetBusy(false);
                Log("Uninstall failed: " + ex.Message);
            }
        }

        private Uri ValidateServerUrl()
        {
            Uri uri;
            if (!Uri.TryCreate(serverUrl.Text.Trim(), UriKind.Absolute, out uri) ||
                !string.Equals(uri.Scheme, Uri.UriSchemeHttps, StringComparison.OrdinalIgnoreCase))
                throw new InvalidOperationException("Server URL must be a valid HTTPS address.");
            return uri;
        }

        private static string CreateSecureTemporaryFolder()
        {
            string folder = Path.Combine(Path.GetTempPath(), "UNICOIL-Agent-Setup-" + Guid.NewGuid().ToString("N"));
            Directory.CreateDirectory(folder);
            var security = new DirectorySecurity();
            security.SetAccessRuleProtection(true, false);
            var inheritance = InheritanceFlags.ContainerInherit | InheritanceFlags.ObjectInherit;
            security.AddAccessRule(new FileSystemAccessRule(new SecurityIdentifier(WellKnownSidType.LocalSystemSid, null),
                FileSystemRights.FullControl, inheritance, PropagationFlags.None, AccessControlType.Allow));
            security.AddAccessRule(new FileSystemAccessRule(new SecurityIdentifier(WellKnownSidType.BuiltinAdministratorsSid, null),
                FileSystemRights.FullControl, inheritance, PropagationFlags.None, AccessControlType.Allow));
            Directory.SetAccessControl(folder, security);
            return folder;
        }

        private static void ExtractResource(string name, string folder)
        {
            using (Stream input = Assembly.GetExecutingAssembly().GetManifestResourceStream(name))
            {
                if (input == null) throw new InvalidOperationException("Embedded installer resource is missing: " + name);
                using (var outputFile = File.Create(Path.Combine(folder, name))) input.CopyTo(outputFile);
            }
        }

        private void RunPowerShell(string script, string arguments)
        {
            var start = new ProcessStartInfo {
                FileName = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.System),
                    "WindowsPowerShell\\v1.0\\powershell.exe"),
                Arguments = "-NoProfile -NonInteractive -ExecutionPolicy Bypass -File " + Quote(script) + " " + arguments,
                UseShellExecute = false,
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                CreateNoWindow = true
            };
            using (Process process = Process.Start(start))
            {
                string standardOutput = process.StandardOutput.ReadToEnd();
                string standardError = process.StandardError.ReadToEnd();
                process.WaitForExit();
                if (!string.IsNullOrWhiteSpace(standardOutput)) Log(standardOutput.Trim());
                if (process.ExitCode != 0)
                    throw new InvalidOperationException(string.IsNullOrWhiteSpace(standardError) ?
                        "PowerShell installer returned exit code " + process.ExitCode + "." : standardError.Trim());
            }
        }

        private static string Quote(string value)
        {
            return "\"" + value.Replace("\"", "\\\"") + "\"";
        }

        private static void TryDeleteFolder(string folder)
        {
            try { if (Directory.Exists(folder)) Directory.Delete(folder, true); }
            catch { }
        }

        private void SetBusy(bool busy)
        {
            testButton.Enabled = !busy;
            installButton.Enabled = !busy;
            uninstallButton.Enabled = !busy;
            Cursor = busy ? Cursors.WaitCursor : Cursors.Default;
            Application.DoEvents();
        }

        private void Log(string message)
        {
            output.AppendText(DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + "  " + message + Environment.NewLine);
        }
    }
}
